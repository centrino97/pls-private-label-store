# Product Page Redesign Plan
## Matching Private Label Skin Care Design

**Reference Site:** https://privatelabelskincare.com.au/products/milk-cleanser  
**Current Implementation:** Modal-based configurator  
**Target:** Inline options with dynamic pricing

---

## Key Changes Required

### 1. **Move Configurator from Modal to Inline**

**Current Flow:**
- Product header → "Configure & Order" button → Modal opens → Options → Add to Cart

**Target Flow:**
- Product header → Inline options (packaging, quantity, label) → Dynamic total → Add to Cart

**Implementation:**
- Modify `render_product_header()` to include inline options
- Remove modal wrapper, render options directly in header
- Update JavaScript to handle inline option changes

---

### 2. **Restructure Product Header Layout**

**Current Structure:**
```
[Product Image]  [Product Title]
[Gallery]        [Description]
                 [Starting Price]
                 [Configure & Order Button]
```

**Target Structure:**
```
[Product Image]  [Product Title]
[Gallery]        [Description]
                 [Starting Price]
                 
                 [Choose your Packaging ▼]
                 [Choose your Qty ▼]
                 [Label Application ○ ○]
                 
                 [Total: $XXX.XX]
                 [Add to cart]
```

---

### 3. **Add Inline Option Selectors**

**Packaging Dropdown:**
- Extract packaging options from `basics_json` (e.g., "Luxe (125ml)", "Pure (200ml)", "Natural (200ml)")
- Render as `<select>` dropdown
- Show price per unit for each option

**Quantity Dropdown:**
- Map pack tiers to quantity options
- Format: "x10 units ($19.ea)", "x50 units ($17.ea)", etc.
- Calculate price per unit based on selected tier

**Label Application Radio:**
- Two options: "No thanks – I'll do it myself!" (default) and "Yes – $2 per unit"
- Render as radio buttons
- Add $2 per unit to total if selected

---

### 4. **Dynamic Total Calculation**

**Requirements:**
- Update total in real-time as options change
- Show: `Total: $XXX.XX`
- Calculate: (Base price × quantity) + (Label fee × quantity if selected)

**JavaScript Updates:**
- Listen to option changes
- Recalculate total
- Update DOM immediately

---

### 5. **Visual Enhancements**

**Benefits Section:**
- Make "The Benefits" section more prominent
- Use visual icons/images (currently text-based)
- Match reference site's visual style

**Key Ingredients:**
- Enhance visual presentation
- Add circular icons/images
- Match reference site's layout

---

## Implementation Steps

### Step 1: Create Inline Configurator Component

**File:** `includes/frontend/class-pls-frontend-display.php`

**New Method:** `render_inline_product_options()`
- Render packaging dropdown
- Render quantity dropdown  
- Render label application radio
- Render dynamic total display
- Render add to cart button

### Step 2: Update Product Header

**Modify:** `render_product_header()`
- Add call to `render_inline_product_options()` after price display
- Remove "Configure & Order" button
- Add "Add to Cart" button (enabled when options selected)

### Step 3: Update JavaScript

**File:** `assets/js/offers.js`

**Add:**
- Option change handlers
- Total calculation logic
- Add to cart form submission
- Variation selection based on options

### Step 4: Update CSS

**File:** `assets/css/frontend-display.css`

**Add:**
- Styles for inline options
- Dropdown styling
- Radio button styling
- Total display styling
- Match reference site's visual design

---

## Code Structure

### Inline Options HTML Structure

```html
<div class="pls-product-options-inline">
    <!-- Packaging Dropdown -->
    <div class="pls-option-group">
        <label>Choose your Packaging</label>
        <select name="packaging" class="pls-packaging-select">
            <option value="luxe-125ml">Luxe (125ml)</option>
            <option value="pure-200ml">Pure (200ml)</option>
            <option value="natural-200ml">Natural (200ml)</option>
        </select>
    </div>
    
    <!-- Quantity Dropdown -->
    <div class="pls-option-group">
        <label>Choose your Qty</label>
        <select name="quantity" class="pls-quantity-select">
            <option value="tier-1" data-units="10" data-price="19.00">x10 units ($19.ea)</option>
            <option value="tier-2" data-units="50" data-price="17.00">x50 units ($17.ea)</option>
            <option value="tier-3" data-units="100" data-price="16.00">x100 units ($16.ea)</option>
            <option value="tier-4" data-units="200" data-price="14.50">x200 units ($14.50.ea)</option>
        </select>
    </div>
    
    <!-- Label Application Radio -->
    <div class="pls-option-group">
        <label>Would you like us to apply your labels?</label>
        <div class="pls-radio-group">
            <label>
                <input type="radio" name="label_application" value="no" checked>
                No thanks – I'll do it myself!
            </label>
            <label>
                <input type="radio" name="label_application" value="yes" data-price="2.00">
                Yes – $2 per unit
            </label>
        </div>
    </div>
    
    <!-- Dynamic Total -->
    <div class="pls-total-display">
        <strong>Total: <span id="pls-total-amount">$190.00</span></strong>
    </div>
    
    <!-- Add to Cart Button -->
    <button type="button" class="pls-add-to-cart-button button">
        Add to cart
    </button>
</div>
```

---

## Data Mapping

### Packaging Options
- Source: `basics_json` → Find attribute with label containing "Packaging" or "Package Type"
- Map to WooCommerce variations or attributes

### Quantity Options
- Source: Pack tiers from `pa_pack-tier` attribute
- Map tier to units and price per unit
- Format display: "x{units} units (${price}.ea)"

### Label Application
- Source: `basics_json` → Find attribute with label containing "Label Application"
- Default: "No" (free)
- Option: "Yes" (+$2 per unit)

---

## JavaScript Logic

### Total Calculation

```javascript
function calculateTotal() {
    const quantitySelect = document.querySelector('.pls-quantity-select');
    const labelRadio = document.querySelector('input[name="label_application"]:checked');
    
    const selectedOption = quantitySelect.options[quantitySelect.selectedIndex];
    const units = parseInt(selectedOption.dataset.units);
    const pricePerUnit = parseFloat(selectedOption.dataset.price);
    
    let labelFee = 0;
    if (labelRadio && labelRadio.value === 'yes') {
        labelFee = parseFloat(labelRadio.dataset.price);
    }
    
    const baseTotal = units * pricePerUnit;
    const labelTotal = units * labelFee;
    const grandTotal = baseTotal + labelTotal;
    
    document.getElementById('pls-total-amount').textContent = formatPrice(grandTotal);
}
```

### Add to Cart Handler

```javascript
function handleAddToCart() {
    const quantitySelect = document.querySelector('.pls-quantity-select');
    const selectedTier = quantitySelect.value;
    
    // Find variation ID for selected tier
    const variationId = getVariationForTier(selectedTier);
    
    // Submit WooCommerce add to cart form
    const form = document.querySelector('.variations_form');
    form.querySelector('input[name="variation_id"]').value = variationId;
    form.submit();
}
```

---

## CSS Styling

### Match Reference Site Design

```css
.pls-product-options-inline {
    margin: 2rem 0;
    padding: 1.5rem;
    background: #fff;
    border-radius: 8px;
}

.pls-option-group {
    margin-bottom: 1.5rem;
}

.pls-option-group label {
    display: block;
    font-weight: 600;
    margin-bottom: 0.5rem;
    color: var(--pls-gray-900);
}

.pls-packaging-select,
.pls-quantity-select {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid var(--pls-gray-300);
    border-radius: 4px;
    font-size: 1rem;
}

.pls-radio-group {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.pls-radio-group label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-weight: 400;
    cursor: pointer;
}

.pls-total-display {
    margin: 1.5rem 0;
    padding: 1rem;
    background: var(--pls-gray-50);
    border-radius: 4px;
    text-align: center;
    font-size: 1.25rem;
}

.pls-add-to-cart-button {
    width: 100%;
    padding: 1rem;
    background: var(--pls-primary);
    color: #fff;
    border: none;
    border-radius: 4px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
}
```

---

## Testing Checklist

- [ ] Packaging dropdown shows correct options
- [ ] Quantity dropdown shows correct tiers with prices
- [ ] Label application radio works correctly
- [ ] Total updates when options change
- [ ] Add to cart button submits correct variation
- [ ] Mobile responsive design
- [ ] Visual design matches reference site
- [ ] Benefits section enhanced visually
- [ ] Key ingredients section enhanced visually

---

## Migration Path

1. **Phase 1:** Add inline options alongside existing modal (A/B test)
2. **Phase 2:** Make inline options default, keep modal as fallback
3. **Phase 3:** Remove modal completely, use inline only

---

## Notes

- Maintain backward compatibility during transition
- Ensure WooCommerce variation selection works correctly
- Test with all pack tiers
- Verify label application fee calculation
- Ensure mobile responsiveness
