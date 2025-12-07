# Elementor Integration

This plugin registers:
- Elementor Widgets:
  - PLS Configurator
  - PLS Bundle Offer
- Elementor Dynamic Tags:
  - PLS Pack Units (stub)

Registration:
- `elementor/widgets/register`
- `elementor/dynamic_tags/register`

Files:
- `includes/elementor/class-pls-elementor.php`
- `includes/elementor/widgets/*`
- `includes/elementor/dynamic-tags/*`

## Elementor Pro Popup workflow
The Bundle Offer widget supports a "Popup Selector" control.
Designers can configure an Elementor Pro Popup to open on click "By selector"
and set that selector to the button class name used in the widget.
