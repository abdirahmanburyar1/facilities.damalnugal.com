<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Low Stock Notification</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #f8f9fa;
            padding: 20px;
            text-align: center;
            border-bottom: 3px solid #e9ecef;
        }
        .content {
            padding: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        .warning {
            color: #e67e22;
            font-weight: bold;
        }
        .critical {
            color: #e74c3c;
            font-weight: bold;
        }
        .status-icon {
            display: inline-block;
            vertical-align: middle;
            margin-right: 5px;
        }
        .footer {
            background-color: #f8f9fa;
            padding: 15px;
            text-align: center;
            font-size: 12px;
            color: #6c757d;
            border-top: 1px solid #e9ecef;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Low Stock Alert</h1>
            <p>Warehouse Management System</p>
        </div>
        
        <div class="content">
            <p>Hello,</p>
            
            <p>This is an automated notification to inform you that the following items are currently low in stock and may require replenishment:</p>
            
            <table>
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Batch Number</th>
                        <th>Location</th>
                        <th>Warehouse</th>
                        <th>Quantity</th>
                        <th>Reorder Level</th>
                        <th>Expiry Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($lowStockItems as $item)
                    <tr>
                        <td>{{ $item->product->name }}</td>
                        <td>{{ $item->batch_number }}</td>
                        <td>{{ $item->location }}</td>
                        <td>{{ $item->warehouse->name }}</td>
                        <td>{{ $item->quantity }}</td>
                        <td>{{ $item->reorder_level }}</td>
                        <td>{{ $item->expiry_date ? date('Y-m-d', strtotime($item->expiry_date)) : 'N/A' }}</td>
                        <td class="{{ $item->quantity <= ($item->reorder_level / 2) ? 'critical' : 'warning' }}">
                            @if($item->quantity <= ($item->reorder_level / 2))
                                <span class="status-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#e74c3c" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <circle cx="12" cy="12" r="10"></circle>
                                        <line x1="12" y1="8" x2="12" y2="12"></line>
                                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                                    </svg>
                                </span>
                                Critical
                            @else
                                <span class="status-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#e67e22" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                                        <line x1="12" y1="9" x2="12" y2="13"></line>
                                        <line x1="12" y1="17" x2="12.01" y2="17"></line>
                                    </svg>
                                </span>
                                Warning
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            
            <p>Please take appropriate action to replenish these items to maintain optimal inventory levels.</p>
            
            <p>Thank you,<br>
            Warehouse Management System</p>
        </div>
        
        <div class="footer">
            <p>This is an automated message. Please do not reply to this email.</p>
            <p>&copy; {{ date('Y') }} Warehouse Management System</p>
        </div>
    </div>
</body>
</html>
