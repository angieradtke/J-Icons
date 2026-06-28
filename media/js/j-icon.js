/**
 * /**
 *  * @package     Joomla.Plugin
 *  * @subpackage  System.jicon
 *  * @copyright   (C) 2026 Angie Radtke
 *  * @license     GNU/GPL
 *  */


class JIcon extends HTMLElement {
	static register() {
		if (!customElements.get('j-icon')) {
			customElements.define('j-icon', JIcon);
		}
	}

	/**
	 * Constructor — Shadow DOM initialisieren
	 */
	constructor() {
		super();
		this.attachShadow({ mode: 'open' });
	}

	/**
	 * connectedCallback — ausgelöst wenn Element in DOM eingefügt
	 */
	connectedCallback() {
		this.render();
	}

	/**
	 * attributeChangedCallback — Überwachung von Attributänderungen
	 */

	static get observedAttributes() {
		return ['name','size','color','animation','title','aria-label','aria-hidden'];
	}
	attributeChangedCallback(name, oldValue, newValue) {
		if (oldValue !== newValue) {
			this.render();
		}
	}

	/**
	 * Validierungsmethoden
	 */
	/**
	 * Validierungsmethoden
	 */
	isEmpty(value) {
		return value == null || String(value).trim() === '';
	}

	isValidName(name) {
		if (this.isEmpty(name)) return true;

		name = String(name).trim();

		if (!name.startsWith('#')) return false;

		const text = name.slice(1);
		return /^[a-zA-Z0-9\-_]+$/.test(text);
	}

	isValidText(text) {
		if (this.isEmpty(text)) return true;

		return !/[<>"{}`]/.test(String(text));
	}

	isValidAriaHidden(value) {
		if (this.isEmpty(value)) return true;

		value = String(value).trim();

		return value === 'true' || value === 'false';
	}

	isValidSize(size) {
		if (this.isEmpty(size)) return true;

		const cleanSize = String(size).trim();

		if (!/^[0-9]+(\.[0-9]+)?$/.test(cleanSize)) {
			return false;
		}

		const num = Number(cleanSize);

		return Number.isFinite(num) && num > 0 && num <= 100;
	}

	isValidColor(color) {
		if (this.isEmpty(color)) return true;
		color = String(color).trim();
		if (color.toLowerCase() === 'currentcolor') {
			return true;
		}

		const hex = /^#[0-9A-Fa-f]{3}([0-9A-Fa-f]{3})?([0-9A-Fa-f]{2})?$/;
		const rgb = /^rgb\(\s*\d{1,3}\s*,\s*\d{1,3}\s*,\s*\d{1,3}\s*\)$/;
		const rgba = /^rgba\(\s*\d{1,3}\s*,\s*\d{1,3}\s*,\s*\d{1,3}\s*,\s*[\d.]+\s*\)$/;
		const oklab = /^oklab\(\s*[\d.%]+\s+[\d.\-]+\s+[\d.\-]+\s*(\/\s*[\d.]+)?\s*\)$/;
		const hsla = /^hsla?\(\s*\d{1,3}\s*,\s*\d{1,3}%\s*,\s*\d{1,3}%\s*(,\s*[\d.]+)?\s*\)$/;

		return (
			hex.test(color) ||
			rgb.test(color) ||
			rgba.test(color) ||
			oklab.test(color) ||
			hsla.test(color)
		);
	}

	isValidAnimation(animation) {
		if (this.isEmpty(animation)) return true;

		return /^[a-zA-Z0-9\-_]+$/.test(String(animation).trim());
	}

	/**
	 * render() — Shadow DOM mit Icon SVG und Styles rendern
	 */
	render() {
		const rawName = this.getAttribute('name') || '';
		const rawSize = this.getAttribute('size') || '1';
		const rawColor = this.getAttribute('color') || 'currentColor';
		const rawAnimation = this.getAttribute('animation') || '';
		const rawAriaLabel = this.getAttribute('aria-label');
		const rawAriaHidden = this.getAttribute('aria-hidden');
		const rawTitle = this.getAttribute('title');

		// Validierung
		if (!this.isValidName(rawName)) {
			console.warn('j-icon: ungültiger name');
			return;
		}
		if (!this.isValidSize(rawSize)) {
			console.warn('j-icon: ungültiger size');
			return;
		}
		if (!this.isValidColor(rawColor)) {
			console.warn('j-icon: ungültiger color');
			return;
		}
		if (!this.isValidAnimation(rawAnimation)) {
			console.warn('j-icon: ungültiger animation');
			return;
		}
		if (!this.isValidText(rawAriaLabel)) {
			console.warn('j-icon: ungültiger aria-label');
			return;
		}
		if (!this.isValidText(rawTitle)) {
			console.warn('j-icon: ungültiger title');
			return;
		}
		if (rawAriaHidden && !this.isValidAriaHidden(rawAriaHidden)) {
			console.warn('j-icon: aria-hidden nur true/false');
			return;
		}

		const name = rawName;
		const size = rawSize;
		const color = rawColor;
		const animation = rawAnimation;
		const ariaLabel = rawAriaLabel;
		const ariaHidden = rawAriaHidden === 'true';
		const title = rawTitle;

		// Icon ID für Sprite-Reference
		const iconId = `${name}`;
		const animationClass = `${animation ? '' + animation : ''}`;

		// Barrierefreiheit: Title nur wenn nicht aria-hidden
		const titleEl = !ariaHidden && title ? `<title>${this.escapeHtml(title)}</title>` : '';

		// Pfad zum SVG-Sprite aus globaler Config
		const path = window.JIconConfig?.spritePath || '/icons/sprite.svg';

		const svg = `
      <svg 
        xmlns="http://www.w3.org/2000/svg"
        class="j-icon ${animationClass}"
        ${ariaHidden ? 'aria-hidden="true"' : ''}
        ${ariaLabel ? `aria-label="${this.escapeHtml(ariaLabel)}"` : ''}
      >
        ${titleEl}
        <use href="${path}${iconId}" />
      </svg>
    `;

		const styles = `
      :host {
        --j-icon-inline-color: ${color};
        --j-icon-size: ${size};
        --j-icon-color: var(--j-icon-inline-color, var(--j-icon-color-primary, currentColor));
        --j-icon-width: calc(1.5em * ${size});
        --j-icon-display: inline-block;
        --j-icon-vertical-align: -0.125em;
      }

      .j-icon {
        aspect-ratio: 1;
        box-sizing: content-box;
        display: var(--j-icon-display);
        fill: var(--j-icon-color);
        overflow: visible;
        vertical-align: var(--j-icon-vertical-align);
        width: var(--j-icon-width);
        color: var(--j-icon-color);
      }

      /* Animationen */
      @keyframes j-icon-spin {
        0% {
          transform: rotate(0deg);
        }
        100% {
          transform: rotate(360deg);
        }
      }

      @keyframes j-icon-pulse {
        0%, 100% {
          opacity: 1;
        }
        50% {
          opacity: 0.4;
        }
      }

      /* Accessibility  */
      @media (prefers-reduced-motion: no-preference) {
        .j-icon-spin {
          animation: j-icon-spin 2s linear infinite;
        }

        .j-icon-pulse {
          animation: j-icon-pulse 2s steps(5) infinite;
        }
      }
    `;

		this.shadowRoot.innerHTML = `<style>${styles}</style>${svg}`;
	}

	/**
	 * escapeHtml() — Sicheres Escapen für HTML-Attribute
	 * @param {string} text — zu escapender Text
	 * @returns {string} escapierter HTML-Text
	 */
	escapeHtml(text) {
		const div = document.createElement('div');
		div.textContent = text;
		return div.innerHTML;
	}

	/**
	 * setIcon() — Programmatische API: Icon wechseln
	 * @param {string} name — Icon-Name
	 */
	setIcon(name) {
		this.setAttribute('name', name);
	}

	/**
	 * getIcon() — Icon-Name auslesen
	 * @returns {string} aktueller Icon-Name
	 */
	getIcon() {
		return this.getAttribute('name');
	}

	/**
	 * setSize() — Größe ändern
	 * @param {string} size — CSS-Größenwert (z.B. '1.5', '2')
	 */
	setSize(size) {
		this.setAttribute('size', size);
	}

	/**
	 * setColor() — Farbe ändern
	 * @param {string} color — CSS-Farbwert
	 */
	setColor(color) {
		this.setAttribute('color', color);
	}

	/**
	 * focus() — Fokus-Handling für interaktive Icons
	 * Versucht Fokus auf parent Button/Link zu setzen, fallback auf Element selbst
	 * @param {Object} options — Focus-Optionen
	 */
	focus(options) {
		const focusable = this.closest('button, a, [role="button"], [tabindex]');
		if (focusable) {
			focusable.focus(options);
		} else {
			super.focus(options);
		}
	}
}

/**
 * Auto-Register wenn nicht manuell geladen
 */
if (typeof window !== 'undefined') {
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', () => JIcon.register());
	} else {
		JIcon.register();
	}
}

/**
 * Export für Module
 */
if (typeof module !== 'undefined' && module.exports) {
	module.exports = JIcon;
}
