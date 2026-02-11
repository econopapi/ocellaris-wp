/**
 * Ocellaris MSI Control - Checkout Script
 * 
 * Controla la disponibilidad de cuotas (MSI) en el checkout de MercadoPago
 * basándose en la whitelist de productos configurada en el admin.
 * 
 * Usa un patrón de polling periódico (similar al shipping filter) para
 * garantizar resiliencia ante recargas AJAX del checkout (e.g. Envia.com).
 * 
 * @package Ocellaris Custom Astra
 */
(function($) {
	'use strict';

	// Config passed via wp_localize_script
	var config = window.OcellarisMSI || {};

	// Guard: if MSI control is disabled, do nothing
	if (!config.enabled) {
		return;
	}

	var msiStatus        = config.msiStatus;        // 'all_msi' | 'mixed' | 'none_msi'
	var allowedMonths    = config.allowedMonths;     // array of allowed month values e.g. [1, 3, 6]
	var mixedCartMessage = config.mixedCartMessage;  // disclaimer text
	var disclaimerShown  = false;

	// Track the last select element we processed to detect DOM replacements
	var lastProcessedSelect = null;
	var lastProcessedOptionsCount = 0;

	/**
	 * Should we restrict installments?
	 * 'all_msi' → no restriction (user can pick MSI)
	 * 'mixed' or 'none_msi' → force 1 month
	 */
	function shouldRestrict() {
		return msiStatus === 'mixed' || msiStatus === 'none_msi';
	}

	/**
	 * Check if the select has already been processed correctly
	 * Returns true if no action is needed
	 */
	function isAlreadyProcessed(selectEl) {
		if (!selectEl) return false;

		// If the DOM element changed (checkout reloaded), we need to reprocess
		if (selectEl !== lastProcessedSelect) return false;

		var currentOptionsCount = selectEl.querySelectorAll('option[value]:not([value=""])').length;

		// If options changed (MP SDK repopulated), we need to reprocess
		if (currentOptionsCount !== lastProcessedOptionsCount) return false;

		if (shouldRestrict()) {
			// For restricted mode: check it's locked to value "1" and disabled
			return selectEl.value === '1' && selectEl.disabled === true;
		} else if (msiStatus === 'all_msi' && allowedMonths && allowedMonths.length > 0) {
			// For filter mode: check no disallowed options remain
			var options = selectEl.querySelectorAll('option');
			var hasDisallowed = false;
			options.forEach(function(opt) {
				var val = parseInt(opt.value, 10);
				if (opt.value === '' || opt.value === '1') return;
				if (!isNaN(val) && !allowedMonths.includes(val)) {
					hasDisallowed = true;
				}
			});
			return !hasDisallowed;
		}

		return true;
	}

	/**
	 * Lock the installments select to "1 mensualidad" only
	 */
	function lockInstallments(selectEl) {
		if (!selectEl) return;

		// Find the option with value "1" (1 mensualidad)
		var optionOne = selectEl.querySelector('option[value="1"]');

		if (!optionOne) {
			// If option "1" doesn't exist yet, wait for it
			return;
		}

		// Set value to 1
		selectEl.value = '1';

		// Remove all other options except value="1" and the placeholder
		var options = selectEl.querySelectorAll('option');
		options.forEach(function(opt) {
			if (opt.value !== '1' && opt.value !== '') {
				opt.remove();
			}
		});

		// Disable the select so user can't change it
		selectEl.disabled = true;
		selectEl.style.opacity = '0.7';
		selectEl.style.cursor = 'not-allowed';

		// Also update the hidden installments input that MercadoPago uses
		var hiddenInstallments = document.getElementById('cardInstallments');
		if (hiddenInstallments) {
			hiddenInstallments.value = '1';
		}

		// Trigger change event so MP picks it up
		var event = new Event('change', { bubbles: true });
		selectEl.dispatchEvent(event);

		// Show disclaimer if needed
		if (msiStatus === 'mixed' && !disclaimerShown) {
			showMixedCartDisclaimer(selectEl);
		}
	}

	/**
	 * For 'all_msi' status: filter options to only show allowed months
	 */
	function filterInstallmentOptions(selectEl) {
		if (!selectEl || !allowedMonths || allowedMonths.length === 0) return;

		var options = selectEl.querySelectorAll('option');
		options.forEach(function(opt) {
			var val = parseInt(opt.value, 10);
			// Keep placeholder (empty value) and allowed months
			if (opt.value === '' || opt.value === '1') return; // always keep 1 month
			if (!isNaN(val) && !allowedMonths.includes(val)) {
				opt.remove();
			}
		});
	}

	/**
	 * Show disclaimer message for mixed cart scenario
	 */
	function showMixedCartDisclaimer(selectEl) {
		if (disclaimerShown) return;

		// Check if disclaimer already exists in DOM (could survive partial reloads)
		if (document.getElementById('ocellaris-msi-disclaimer')) {
			disclaimerShown = true;
			return;
		}

		var container = selectEl.closest('.mp-checkout-custom-installments-select-container') 
			|| selectEl.parentElement;
		
		if (!container) return;

		var disclaimer = document.createElement('div');
		disclaimer.id = 'ocellaris-msi-disclaimer';
		disclaimer.className = 'ocellaris-msi-disclaimer';
		disclaimer.innerHTML = '<span class="ocellaris-msi-disclaimer-icon">ⓘ</span>' +
			'<span class="ocellaris-msi-disclaimer-text">' + mixedCartMessage + '</span>';

		container.appendChild(disclaimer);
		disclaimerShown = true;
	}

	/**
	 * Process the installments select element
	 * This is the core function that gets called repeatedly by the polling interval
	 */
	function processInstallmentsSelect() {
		var selectEl = document.getElementById('form-checkout__installments');
		if (!selectEl) return;

		// Check if the select has real options loaded (not just placeholder)
		var realOptions = selectEl.querySelectorAll('option[value]:not([value=""])');
		if (realOptions.length === 0) return;

		// Skip if already correctly processed (avoid unnecessary DOM manipulation)
		if (isAlreadyProcessed(selectEl)) return;

		if (shouldRestrict()) {
			lockInstallments(selectEl);
		} else if (msiStatus === 'all_msi' && allowedMonths && allowedMonths.length > 0) {
			// All products are MSI-eligible, but filter to only allowed months
			filterInstallmentOptions(selectEl);
		}

		// Track what we processed so we can detect changes
		lastProcessedSelect = selectEl;
		lastProcessedOptionsCount = selectEl.querySelectorAll('option[value]:not([value=""])').length;
	}

	/**
	 * Reset state when checkout is fully reloaded
	 * Called on WooCommerce events that indicate the checkout DOM was replaced
	 */
	function resetState() {
		disclaimerShown = false;
		lastProcessedSelect = null;
		lastProcessedOptionsCount = 0;

		// Remove existing disclaimer (DOM may have been replaced so check again)
		var existing = document.getElementById('ocellaris-msi-disclaimer');
		if (existing) existing.remove();
	}

	/**
	 * Initialize on checkout
	 * Uses a polling interval (like the shipping filter) for resilience
	 * against AJAX reloads from Envia.com or WooCommerce checkout updates.
	 */
	function init() {
		// Try processing immediately
		processInstallmentsSelect();

		// Set up aggressive polling interval — same pattern as shipping filter
		// This ensures we catch the select even after full checkout DOM replacements
		setInterval(function() {
			processInstallmentsSelect();
		}, 2000);

		// Also react immediately to WooCommerce checkout events for faster response
		$(document.body).on('updated_checkout', function() {
			resetState();
			// Process with small delays to catch MP SDK re-rendering
			setTimeout(processInstallmentsSelect, 300);
			setTimeout(processInstallmentsSelect, 800);
			setTimeout(processInstallmentsSelect, 1500);
		});

		$(document.body).on('payment_method_selected', function() {
			resetState();
			setTimeout(processInstallmentsSelect, 300);
			setTimeout(processInstallmentsSelect, 800);
		});
	}

	// Start when DOM is ready
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}

})(jQuery);
