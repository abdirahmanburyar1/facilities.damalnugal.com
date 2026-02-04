// Computed properties for inventory status counts
const inStockCount = computed(() => {
    return props.inventories.data.filter(item => item.quantity > item.reorder_level && item.is_active).length;
});

const lowStockCount = computed(() => {
    return props.inventories.data.filter(item => 
        item.quantity > 0 && 
        item.quantity <= item.reorder_level && 
        item.is_active
    ).length;
});

const outOfStockCount = computed(() => {
    return props.inventories.data.filter(item => item.quantity === 0 && item.is_active).length;
});

const expiredCount = computed(() => {
    return props.inventories.data.filter(item => isExpired(item) && item.is_active).length;
});
