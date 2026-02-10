/**
 * Ocellaris MSI Control - Checkout Script
 * 
 * Controla la disponibilidad de cuotas (MSI) en el checkout de MercadoPago
 * basándose en la whitelist de productos configurada en el admin.
 * 
 * @package Ocellaris Custom Astra
 * @since 1.1.0
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
	var observerActive   = false;

	/**
	 * Should we restrict installments?
	 * 'all_msi' → no restriction (user can pick MSI)
	 * 'mixed' or 'none_msi' → force 1 month
	 */
	function shouldRestrict() {
		return msiStatus === 'mixed' || msiStatus === 'none_msi';
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
	 */
	function processInstallmentsSelect() {
		var selectEl = document.getElementById('form-checkout__installments');
		if (!selectEl) return;

		// Check if the select has real options loaded (not just placeholder)
		var realOptions = selectEl.querySelectorAll('option[value]:not([value=""])');
		if (realOptions.length === 0) return;

		if (shouldRestrict()) {
			lockInstallments(selectEl);
		} else if (msiStatus === 'all_msi' && allowedMonths && allowedMonths.length > 0) {
			// All products are MSI-eligible, but filter to only allowed months
			filterInstallmentOptions(selectEl);
		}
	}

	/**
	 * Set up MutationObserver to watch for installments select changes
	 * MercadoPago populates this select dynamically via its SDK
	 */
	function setupObserver() {
		if (observerActive) return;

		// Target: the entire MP checkout container
		var target = document.getElementById('mp-checkout-custom-container') 
			|| document.querySelector('.mp-checkout-container')
			|| document.body;

		var observer = new MutationObserver(function(mutations) {
			mutations.forEach(function(mutation) {
				// Check if installments select was added or its children changed
				if (mutation.type === 'childList') {
					var selectEl = document.getElementById('form-checkout__installments');
					if (selectEl) {
						var realOptions = selectEl.querySelectorAll('option[value]:not([value=""])');
						if (realOptions.length > 0) {
							// Small delay to ensure all options are loaded
							setTimeout(processInstallmentsSelect, 100);
						}
					}
				}
			});
		});

		observer.observe(target, {
			childList: true,
			subtree: true
		});

		observerActive = true;

		// Also observe the select itself once it exists
		var checkSelect = setInterval(function() {
			var selectEl = document.getElementById('form-checkout__installments');
			if (selectEl) {
				clearInterval(checkSelect);

				// Observe option changes within the select
				var selectObserver = new MutationObserver(function() {
					var realOptions = selectEl.querySelectorAll('option[value]:not([value=""])');
					if (realOptions.length > 0) {
						setTimeout(processInstallmentsSelect, 150);
					}
				});

				selectObserver.observe(selectEl, {
					childList: true,
					attributes: true
				});
			}
		}, 500);

		// Clean up the interval after 30 seconds as safety net
		setTimeout(function() {
			clearInterval(checkSelect);
		}, 30000);
	}

	/**
	 * Initialize on checkout
	 */
	function init() {
		// Set up the observer immediately
		setupObserver();

		// Also try processing directly in case the select already exists
		processInstallmentsSelect();

		// Re-process after WooCommerce checkout updates (e.g., payment method switch)
		$(document.body).on('updated_checkout payment_method_selected', function() {
			disclaimerShown = false;
			// Remove existing disclaimer
			var existing = document.getElementById('ocellaris-msi-disclaimer');
			if (existing) existing.remove();

			setTimeout(processInstallmentsSelect, 500);
		});
	}

	// Start when DOM is ready
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}

})(jQuery);
