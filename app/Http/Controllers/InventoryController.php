<?php

namespace App\Http\Controllers;

use App\Http\Resources\InventoryResource;
use App\Http\Resources\ProductResource;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\Category;
use App\Models\Dosage;
use App\Models\Facility;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Exception;
use Inertia\Inertia;
use App\Events\InventoryEvent;
use Illuminate\Support\Facades\Event;
use App\Models\IssueQuantityReport;
use App\Models\IssueQuantityItem;
use App\Events\InventoryUpdated;
use Illuminate\Support\Facades\Cache;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\UploadInventory;
use App\Services\InventoryAnalyticsService;
use App\Models\InventoryItem;
use App\Models\Location;

class InventoryController extends Controller
{
	public function index(Request $request)
	{
		try {
			$user = auth()->user();
			$facility = $user->facility;

			if (!$facility) {
				return back()->withErrors(['error' => 'User is not associated with any facility.']);
			}

		// Base query with relationships - filter products by eligible items for this facility type
		$productQuery = Product::query()
			->with([
				'category:id,name',
				'dosage:id,name',
				'inventories.items' => function($query) use ($facility) {
					$query->whereHas('inventory', function($subQuery) use ($facility) {
						$subQuery->where('facility_id', $facility->id);
					});
				}
			])
			->whereHas('eligible', function($query) use ($facility) {
				$query->where('facility_type', $facility->facility_type);
			})
			->where('is_active', true);
	
			// Apply filters
			if ($request->filled('search')) {
				$search = $request->search;
				$productQuery->where(function($q) use ($search) {
					$q->where('products.name', 'like', "%{$search}%")
					  ->orWhereHas('inventories.items', function($sub) use ($search) {
						  $sub->where(function($w) use ($search) {
							  $w->where('barcode', 'like', "%{$search}%")
								->orWhere('batch_number', 'like', "%{$search}%");
						  });
					  });
				});
			}
	
			if ($request->filled('category')) {
				$productQuery->whereHas('category', fn($q) => $q->where('name', $request->category));
			}
			if ($request->filled('dosage')) {
				$productQuery->whereHas('dosage', fn($q) => $q->where('name', $request->dosage));
			}
	
			// Status filter - will be applied after data is loaded but before pagination
			$statusFilter = $request->filled('status') ? $request->status : null;
	
			// Get all products first (without pagination) if status filter is applied
			if ($statusFilter) {
				$allProducts = $productQuery->get();
			} else {
				// Paginate normally if no status filter
				$products = $productQuery->paginate(
					$request->input('per_page', 25),
					['*'],
					'page',
					$request->input('page', 1)
				)->withQueryString();
				$products->setPath(url()->current());
			}
			
		// Add reorder_level and amc to each product using the Product model methods
		$currentProducts = $statusFilter ? $allProducts : $products->getCollection();
		
		// Batch calculate metrics for better performance
		$this->calculateMetricsForProducts($currentProducts, $facility);
			
			// Apply status filter after data is loaded and calculated
			if ($statusFilter) {
				try {
					$filteredCollection = $allProducts->filter(function ($product) use ($statusFilter) {
						try {
							$totalQuantity = $product->inventories->flatMap->items->sum('quantity');
							$reorderLevel = $product->reorder_level ?? 0;
							
							switch ($statusFilter) {
								case 'in_stock':
									if ($reorderLevel <= 0) {
										return $totalQuantity > 0;
									}
									$lowStockThreshold = $reorderLevel * 1.3;
									return $totalQuantity > $lowStockThreshold;
									
								case 'low_stock':
									if ($reorderLevel <= 0) {
										// No reorder level set, cannot be "low stock" - return false
										return false;
									}
									$lowStockThreshold = $reorderLevel * 1.3;
									return $totalQuantity > $reorderLevel && $totalQuantity <= $lowStockThreshold;
									
								case 'low_stock_reorder_level':
									if ($reorderLevel <= 0) return false;
									return $totalQuantity > 0 && $totalQuantity <= $reorderLevel;
									
								case 'out_of_stock':
									return $totalQuantity == 0;
									
								default:
									return true;
							}
						} catch (\Exception $e) {
							return false; // Exclude problematic products
						}
					});
					
					// Create pagination from filtered results
					$perPage = $request->input('per_page', 25);
					$currentPage = $request->input('page', 1);
					$offset = ($currentPage - 1) * $perPage;
					$paginatedItems = $filteredCollection->slice($offset, $perPage)->values();
					
					$products = new \Illuminate\Pagination\LengthAwarePaginator(
						$paginatedItems,
						$filteredCollection->count(),
						$perPage,
						$currentPage,
						[
							'path' => request()->url(),
							'pageName' => 'page',
						]
					);
					$products->withQueryString();
				} catch (\Exception $e) {
					// Fallback: paginate all products when filtering fails so $products is always defined
					$perPage = $request->input('per_page', 25);
					$currentPage = $request->input('page', 1);
					$products = new \Illuminate\Pagination\LengthAwarePaginator(
						$allProducts->forPage($currentPage, $perPage)->values(),
						$allProducts->count(),
						$perPage,
						$currentPage,
						['path' => request()->url(), 'pageName' => 'page']
					);
					$products->withQueryString();
				}
			}
	

	
			// Filters data
			$categories = Category::orderBy('name')->pluck('name')->toArray();
			$dosages = Dosage::orderBy('name')->pluck('name')->toArray();
	
			// Status counts
			$statusCounts = [
				[ 'status' => 'in_stock', 'count' => 0 ],
				[ 'status' => 'low_stock', 'count' => 0 ],
				[ 'status' => 'low_stock_reorder_level', 'count' => 0 ],
				[ 'status' => 'out_of_stock', 'count' => 0 ],
			];
	
		// Calculate status counts using the same query structure as main inventory
		$allProducts = Product::with([
				'category:id,name',
				'dosage:id,name',
				'inventories.items' => function($query) use ($facility) {
					$query->whereHas('inventory', function($subQuery) use ($facility) {
						$subQuery->where('facility_id', $facility->id);
					});
				}
			])
			->whereHas('eligible', function($query) use ($facility) {
				$query->where('facility_type', $facility->facility_type);
			})
			->where('is_active', true)
			->get();
			
		// Batch calculate metrics for better performance
		$this->calculateMetricsForProducts($allProducts, $facility);
		
		// Calculate status counts using pre-calculated metrics
		foreach ($allProducts as $product) {
			$totalQuantity = $product->inventories->flatMap->items->sum('quantity');
			$reorderLevel = $product->reorder_level ?? 0;
			
			if ($totalQuantity <= 0) {
				$statusCounts[3]['count']++; // out_of_stock
			} elseif ($reorderLevel > 0) {
				$lowStockThreshold = $reorderLevel * 1.3;
				
				if ($totalQuantity > $lowStockThreshold) {
					$statusCounts[0]['count']++; // in_stock
				} elseif ($totalQuantity > $reorderLevel && $totalQuantity <= $lowStockThreshold) {
					$statusCounts[1]['count']++; // low_stock
				} else {
					$statusCounts[2]['count']++; // low_stock_reorder_level
				}
			} else {
				// No reorder level set - cannot be "low stock", only in_stock or out_of_stock
				if ($totalQuantity > 0) {
					$statusCounts[0]['count']++; // in_stock
				} else {
					$statusCounts[3]['count']++; // out_of_stock
				}
			}
		}
	
			return Inertia::render('Inventory/Index', [
				'inventories' => InventoryResource::collection($products),
				'inventoryStatusCounts' => $statusCounts,
				'filters' => $request->only(['search', 'per_page', 'page', 'category', 'dosage', 'status', 'location', 'warehouse']),
				'category' => $categories,
				'dosage' => $dosages,
			]);
	
		} catch (\Exception $e) {
			return back()->withErrors(['error' => 'An error occurred while loading inventory data.']);
		}
	}	

	/**
	 * Calculate metrics for a collection of products efficiently
	 */
	private function calculateMetricsForProducts($products, $facility)
	{
		// Set global timeout for the entire batch
		set_time_limit(120);
		
		foreach ($products as $product) {
			try {
				$metrics = $product->calculateInventoryMetrics($facility->id);
				$product->reorder_level = $metrics['reorder_level'];
				$product->amc = $metrics['amc'];
			} catch (\Exception $e) {
				// Use fallback values if calculation fails
				$product->reorder_level = $product->calculateFallbackReorderLevel($facility->id) ?? 0;
				$product->amc = 0;
			}
		}
	}

	/**
	 * Apply status filter to the product query
	 */
	protected function applyStatusFilter($query, $status)
	{
		return $query->whereHas('inventories.items', function($subQuery) use ($status) {
			$subQuery->where('inventory_items.quantity', '>', 0);
		});
	}

	/**
	 * Store a newly created resource in storage.
	 */
	public function store(Request $request)
	{
		try {
		   
		$validated = $request->validate([
			'id' => 'nullable|exists:inventories,id',
			'product_id' => 'required|exists:products,id',
			'warehouse_id' => 'required|exists:warehouses,id',
			'quantity' => 'required|numeric|min:0',
			'manufacturing_date' => 'nullable|date',
			'expiry_date' => 'nullable|date|after:manufacturing_date',
			'batch_number' => 'nullable|string',
			'location' => 'nullable|string',
			'notes' => 'nullable|string',
			'is_active' => 'boolean',
		]);

		$isNew = !$request->id;
		
		$inventory = Inventory::updateOrCreate(
			['id' => $request->id],
			$validated
		);

		// event(new InventoryUpdated());
		
		return response()->json( $request->id ? 'Inventory updated successfully' : 'Inventory created successfully', 200);
		} catch (\Throwable $th) {
			return response()->json($th->getMessage(), 500);
		}
	}

	/**
	 * Display the specified resource.
	 */
	public function show(Inventory $inventory)
	{
		$inventory->load(['product.category', 'product.dosage']);
		return response()->json([
			'success' => true,
			'data' => new InventoryResource($inventory),
		]);
	}

	/**
	 * Remove the specified resource from storage.
	 */
	public function destroy(Inventory $inventory)
	{        
		try {
			$inventory->delete();
			event(new InventoryEvent());
			return response()->json([
				'success' => true,
				'message' => 'Inventory item deleted successfully',
			], 200);
		} catch (\Throwable $th) {
			return response()->json($th->getMessage(), 500);
		}   
	}
	
	public function getLocations(Request $request){
		try {
			$warehouse = $request->input('warehouse');
			
			if (!$warehouse) {
				return response()->json([], 200);
			}

			$locations = Location::where('warehouse', $warehouse)
				->select('id', 'location', 'warehouse')
				->get()
				->map(function($location) {
					return [
						'id' => $location->id,
						'location' => $location->location,
						'warehouse' => $location->warehouse
					];
				});

			return response()->json($locations, 200);
		} catch (\Throwable $th) {
			return response()->json($th->getMessage(), 500);
		}
	}

	public function updateLocation(Request $request)
	{
		$request->validate([
			'inventory_item_id' => 'required|exists:inventory_items,id',
			'location' => 'required|string|max:255'
		]);

		try {
			$inventoryItem = InventoryItem::findOrFail($request->inventory_item_id);
			$inventoryItem->update([
				'location' => $request->location
			]);

			// Trigger inventory update event
			// event(new InventoryUpdated($inventoryItem->inventory));

			return response()->json([
				'success' => true,
				'message' => 'Location updated successfully',
				'data' => $inventoryItem
			]);
		} catch (\Exception $e) {
			return response()->json([
				'success' => false,
				'message' => 'Failed to update location: ' . $e->getMessage()
			], 500);
		}
	}

	/**
	 * Import inventory items from Excel file
	 */
	public function import(Request $request)
	{
		try {
			if (!$request->hasFile('file')) {
				return response()->json([
					'success' => false,
					'message' => 'No file was uploaded'
				], 422);
			}
		
			$file = $request->file('file');
		
			// Validate file type
			$extension = $file->getClientOriginalExtension();
			if (!$file->isValid() || !in_array($extension, ['xlsx', 'xls', 'csv'])) {
				return response()->json([
					'success' => false,
					'message' => 'Invalid file type. Please upload an Excel file (.xlsx, .xls) or CSV file'
				], 422);
			}
		
			// Validate file size (max 50MB)
			if ($file->getSize() > 50 * 1024 * 1024) {
				return response()->json([
					'success' => false,
					'message' => 'File size too large. Maximum allowed size is 50MB'
				], 422);
			}

			$user = auth()->user();
			$facilityId = $user?->facility_id;
			if (!$facilityId) {
				return response()->json([
					'success' => false,
					'message' => 'User is not associated with any facility.'
				], 422);
			}

			$result = DB::transaction(function () use ($file, $facilityId) {
				// Ensure long imports don't time out too early
				set_time_limit(120);

				$import = new UploadInventory((int) $facilityId);
				Excel::import($import, $file);

				if ($import->createdCount === 0) {
					$sampleText = count($import->missingProductsSample) ? implode(', ', $import->missingProductsSample) : '';
					$message = 'No inventory items were imported.';
					if ($import->missingProductRows > 0) {
						$message .= ' Some rows were skipped because the product does not exist or is not eligible for your facility.';
						if ($sampleText !== '') {
							$message .= " Missing (sample): {$sampleText}";
						}
					}
					throw new \Exception($message, 422);
				}

				$warning = null;
				if ($import->missingProductRows > 0) {
					$sampleText = count($import->missingProductsSample) ? implode(', ', $import->missingProductsSample) : '';
					$warning = 'Some rows were skipped because the product does not exist or is not eligible for your facility.';
					if ($sampleText !== '') {
						$warning .= " Missing (sample): {$sampleText}";
					}
				}

				return [
					'created_count' => $import->createdCount,
					'warning' => $warning,
				];
			});

			return response()->json([
				'success' => true,
				'message' => "Imported {$result['created_count']} inventory item(s) successfully.",
				'created_count' => $result['created_count'],
				'warning' => $result['warning'],
			]);
		
		} catch (\Exception $e) {
			if ((int) $e->getCode() === 422) {
				return response()->json([
					'success' => false,
					'message' => $e->getMessage()
				], 422);
			}

			return response()->json([
				'success' => false,
				'message' => 'Import failed: ' . $e->getMessage()
			], 500);
		}
	}


	/**
	 * Check if an item needs reorder action (out of stock)
	 *
	 * @param mixed $item
	 * @return bool
	 */
	private function needsReorderAction($item): bool
	{
		// If item doesn't exist or has zero quantity, it needs reorder
		if (!$item || !isset($item->quantity) || (float) $item->quantity <= 0) {
			return true;
		}
		return false;
	}
}
