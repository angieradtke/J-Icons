Here is the English translation:

---

# J-icon System – Plugin

## What is j-icon?

Joomla System Plugin for SVG icon management. Provides  free FontAwesome icons (solid, regular, brands). Renders icons as inline SVG sprites or Webcompomeent. Old FontAwesome WebFont icons are automatically replaced.

## Installation

1. Upload the ZIP file in the backend: **Extensions > Manage > Install**
2. Enable it: **System > Plugins** > `plg_system_jicon`
3. Configure the plugin

## Configuration

**System > Plugins > plg_system_jicon**

### General

* **Enable Plugin**: On/Off
* **SVG Path**: Path to the icons (default: `plugins/system/jicon/media/icons`)
* **Sprite Injection**: Where the sprite is inserted into the HTML

  * `Body Start`: Directly after `<body>`
  * `Body End`: Before `</body>`
  * `Manual`: Do not auto-inject the sprite (path: `{jicon-sprite}` in the template)
* **FontAwesome BC**: Enables automatic conversion of old `<i class="fa ...">` icons to SVG

### Colors

All colors are CSS custom properties. Defined automatically in `:root`:

* `--j-icon-color-primary` / `--j-icon-color-primary-dark`
* `--j-icon-color-success` / `--j-icon-color-success-dark`
* `--j-icon-color-danger` / `--j-icon-color-danger-dark`
* `--j-icon-color-warning` / `--j-icon-color-warning-dark`
* `--j-icon-color-info` / `--j-icon-color-info-dark`

Adjust colors in the backend → CSS is generated automatically.

## Selecting Icons

Backend icon picker. Important icons (house, search, user, gear, heart, star, check, times, etc.) are preselected by default. All other icons must be added manually. Only selected icons are compiled into the sprite.

### Using the Icon Picker in Forms

The `IconsField` provides a picker containing all available icons. It is automatically enabled through the field type. The icon name is stored as `style/name` (e.g. `solid/bell`, `brands/joomla`).

```xml
<!-- In joomla.xml -->
<field name="icon" type="icons" label="Select Icon" />
```

The picker displays:

* 3 categories (Solid, Regular, Brands)
* Live search field (alphabetical)
* Icon preview
* 25 preselected "important" icons

### Icon Categories

All icons are located under:

`/plugins/system/jicon/media/icons/`

```
solid/           → 1000+ icons
  ├── house.svg
  ├── user.svg
  ├── bell.svg
  └── ...

regular/         → 300+ icons
  ├── star.svg
  ├── heart.svg
  └── ...

brands/          → 500+ icons
  ├── joomla.svg
  ├── github.svg
  ├── facebook.svg
  └── ...
```

Visible in the backend icon picker with live preview.

---

# Frontend Usage

Two options: **SVG direct** or **Web Component**.

## Option 1: SVG with Symbol Reference

```html
<svg class="j-icon j-icon-2xl j-icon-info">
  <use href="#icon-solid-bell"></use>
</svg>

<svg class="j-icon">
  <use href="#icon-solid-user"></use>
</svg>

<svg class="j-icon j-icon-lg j-icon-primary">
  <use href="#icon-brands-joomla"></use>
</svg>
```

### Symbol IDs

Format:

```
#icon-STYLE-ICONNAME
```

* `STYLE`: `solid`, `regular`, or `brands`
* `ICONNAME`: Icon name from the folder (e.g. `bell`, `user`, `joomla`)

Examples:

```
#icon-solid-bell
#icon-regular-heart
#icon-brands-github
```

---

# Option 2: Web Component `<j-icon>`

```html
<j-icon name="#icon-solid-bell" size="1.9" color="#ff0000" animation="j-icon-pulse"></j-icon>

<j-icon name="#icon-regular-user" aria-label="user"></j-icon>

<j-icon name="#icon-brands-joomla" size="2.5" animation="j-icon-spin"></j-icon>
```

## Attributes (all optional)

| Attribute     | Value                         | Default        | Description                                        |
| ------------- | ----------------------------- | -------------- | -------------------------------------------------- |
| `name`        | `#icon-STYLE-NAME`            | —              | Symbol ID, must start with `#`                     |
| `size`        | `0.1`–`100`                   | `1`            | Size multiplier (1.5em × size)                     |
| `color`       | CSS color                     | `currentColor` | Hex, RGB, RGBA, OKLAB, HSL/HSLA, or `currentColor` |
| `animation`   | `j-icon-spin`, `j-icon-pulse` | —              | Animation class                                    |
| `title`       | Text                          | —              | Tooltip (only when not aria-hidden)                |
| `aria-label`  | Text                          | —              | ARIA label for screen readers                      |
| `aria-hidden` | `true`/`false`                | —              | Hide icon from screen readers                      |

## Examples

```html
<!-- Simple -->
<j-icon name="#icon-solid-bell"></j-icon>

<!-- With size and color -->
<j-icon name="#icon-solid-user" size="1.5" color="#0066cc"></j-icon>

<!-- With animation -->
<j-icon name="#icon-solid-spinner" size="2" animation="j-icon-spin"></j-icon>

<!-- With accessibility -->
<j-icon name="#icon-solid-info" aria-label="Information" title="Information"></j-icon>

<!-- Hidden from screen readers -->
<j-icon name="#icon-solid-check" aria-hidden="true"></j-icon>
```

## Validation

All attributes are validated. Invalid values create a warning in the console and stop rendering.

Validation rules:

* `name`: Must start with `#`, followed only by alphanumeric characters plus `-` / `_`
* `size`: Number between 0.1 and 100
* `color`: Hex (#RGB, #RRGGBB, #RRGGBBAA), RGB, RGBA, OKLAB, HSL/HSLA, or `currentColor`
* `animation`: Alphanumeric characters plus `-` / `_`
* `title`, `aria-label`: No HTML tags or special characters (`<>"{}``)
* `aria-hidden`: `true` or `false` (string)

## CSS Custom Properties

The Web Component uses Shadow DOM with CSS variables:

```css
--j-icon-color: Current color (inline + fallback to --j-icon-color-primary)
--j-icon-size: Size multiplier (default: 1)
--j-icon-width: Calculated width = calc(1.5em × size)
--j-icon-display: inline-block
--j-icon-vertical-align: -0.125em
```

The Web Component uses Shadow DOM (encapsulated). External CSS classes do not affect the icon itself, but CSS classes can be applied to the `<j-icon>` container element.

---



---


# CSS Classes

## Sizes

```html
<svg class="j-icon j-icon-2xs"> ... </svg>  <!-- 0.625em -->
<svg class="j-icon j-icon-xs"> ... </svg>   <!-- 0.75em -->
<svg class="j-icon j-icon-sm"> ... </svg>   <!-- 0.875em -->
<svg class="j-icon"> ... </svg>             <!-- 1em (default) -->
<svg class="j-icon j-icon-lg"> ... </svg>   <!-- 1.25em -->
<svg class="j-icon j-icon-xl"> ... </svg>   <!-- 1.5em -->
<svg class="j-icon j-icon-2xl"> ... </svg>  <!-- 2em -->
```

## Colors

```html
<svg class="j-icon j-icon-primary"> ... </svg>
<svg class="j-icon j-icon-success"> ... </svg>
<svg class="j-icon j-icon-danger"> ... </svg>
<svg class="j-icon j-icon-warning"> ... </svg>
<svg class="j-icon j-icon-info"> ... </svg>
```

## Animations

```html
<svg class="j-icon j-icon-spin"> ... </svg>    <!-- Rotates (360°) -->
<svg class="j-icon j-icon-pulse"> ... </svg>   <!-- Blinks (opacity) -->
```

---

# FontAwesome WebFont → SVG (Automatic)

When **FontAwesome BC** is enabled, old icons are automatically replaced.

## Old Syntax

```html
<i class="fa fa-bell"></i>
<span class="fa fa-bell"></span>
<süam class="icon-fw   icon-bell"></i>

```

## Converted To

```html
<svg class="j-icon" aria-hidden="true">
  <use href="#icon-solid-bell"></use>
</svg>
<!-- etc. -->
```

The conversion runs during the `DOMContentLoaded` event.

---

# Sprite Rendering

The plugin uses an inline sprite:

```html
<svg style="display: none;" id="j-icon-sprite">
```

Each selected icon is embedded as a `<symbol>`:

```html
<!-- Generated internally -->
<svg style="display: none;" id="j-icon-sprite" xmlns="http://www.w3.org/2000/svg">

  <symbol id="icon-solid-bell" viewBox="0 0 448 512">
    <path d="M224 0c-17.7..."/>
  </symbol>

  <symbol id="icon-solid-user" viewBox="0 0 448 512">
    <path d="M224 256c70.7..."/>
  </symbol>

  <!-- ... additional icons ... -->

</svg>
```

The sprite is injected during the `onAfterRender` event (plugin phase).

---

# Troubleshooting

## Icon is not visible?

* Is the plugin enabled? (**Plugins > plg_system_jicon = Enabled**)
* Is the icon selected in the picker? (Backend configuration)
* Is Sprite Injection configured correctly? (`Body Start` vs. `Body End` vs. `Manual`)
* If using Manual mode: Is `{jicon-sprite}` present in the template?

## Old FontAwesome icons do not work?

* Enable **FontAwesome BC** in the configuration
* Clear browser cache
* Reload the page

## Wrong icon displayed?

* Check the symbol ID (e.g. `#icon-solid-bell` instead of `#icon-bell`)
* Search all icons in the picker using the search field

## Performance is slow?

* Select only required icons in the picker
* Sprite size is reduced during loading
* CSS/JS files are already minified

---

# Files

```
plugins/system/jicon/
├── jicon.xml                          (Manifest)
├── language/en-GB/
│   ├── plg_system_jicon.ini           (GUI Strings)
│   └── plg_system_jicon.sys.ini       (Extension Strings)
├── media/
│   ├── css/
│   │   ├── j-icon.min.css             (Base Styles + Sizes + Colors + Animations)
│   │   ├── bc-fontawesome.min.css     (FontAwesome BC Styles)
│   │   └── icon-picker.min.css        (Backend Picker UI)
│   ├── js/
│   │   ├── j-icon.js                  (Web Component: <j-icon> Element)
│   │   ├── bc-fontawesome.js          (FontAwesome WebFont → SVG Converter)
│   │   └── icon-picker.js              (Backend Picker Interaction)
│   └── icons/
│       ├── solid/                     (1000+ SVG Icons)
│       ├── regular/                   (300+ SVG Icons)
│       └── brands/                    (500+ SVG Icons)
├── src/
│   ├── Extension/JIcon.php             (Main Plugin Class)
│   ├── Field/IconsField.php            (Backend Icon Picker)
│   ├── Helper/JIconHelper.php          (Sprite Generation + Icon Rendering)
│   └── Service/JIconService.php        (Custom CSS for Colors)
└── services/provider.php               (Joomla DI Container)
```
