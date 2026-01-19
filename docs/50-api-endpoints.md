# API Endpoints

## Admin AJAX

All endpoints require `pls_admin_nonce` unless specified otherwise.

### Products
- `pls_save_product` - Create/update product
- `pls_delete_product` - Delete product
- `pls_sync_product` - Sync to WooCommerce
- `pls_activate_product` - Activate WooCommerce product
- `pls_deactivate_product` - Deactivate WooCommerce product

### Bundles
- `pls_save_bundle` - Create/update bundle
- `pls_get_bundle` - Get bundle details
- `pls_delete_bundle` - Delete bundle
- `pls_sync_bundle` - Sync bundle to WooCommerce

### Attributes
- `pls_save_attribute` - Create/update attribute
- `pls_save_attribute_value` - Create/update attribute value
- `pls_delete_attribute` - Delete attribute
- `pls_delete_attribute_value` - Delete attribute value

### Custom Orders
- `pls_update_custom_order_status` - Update order status
- `pls_get_custom_order_details` - Get order details
- `pls_update_custom_order_financials` - Update financials
- `pls_mark_custom_order_invoiced` - Mark as invoiced
- `pls_mark_custom_order_paid` - Mark as paid

### Commission
- `pls_update_commission_status` - Update commission status
- `pls_bulk_update_commission_status` - Bulk update

### Onboarding
- `pls_start_onboarding` - Start tutorial
- `pls_update_onboarding_step` - Mark step complete
- `pls_complete_onboarding` - Complete tutorial
- `pls_skip_onboarding` - Skip tutorial
- `pls_get_helper_content` - Get helper content

## Response Format

```json
{
  "success": true,
  "data": { ... }
}
```

```json
{
  "success": false,
  "data": {
    "message": "Error message"
  }
}
```
