/**
 * Checkout Field Persistence
 * 
 * Guarda los valores de los campos del checkout en localStorage
 * para restaurarlos cuando el usuario regrese al checkout.
 * 
 * Los campos de dirección ya son persistidos por WooCommerce,
 * este script se encarga de los demás: nombre, apellidos, empresa,
 * teléfono, email y notas del pedido.
 * 
 * @package Ocellaris Custom Astra
 */
(function () {
	'use strict';

	var STORAGE_KEY = 'ocellaris_checkout_fields';

	// Campos que WooCommerce NO persiste de forma nativa
	var fieldIds = [
		'billing_first_name',
		'billing_last_name',
		'billing_company',
		'billing_phone',
		'billing_email',
		'order_comments'
	];

	/**
	 * Obtiene los datos guardados del localStorage.
	 */
	function getSavedData() {
		try {
			var data = localStorage.getItem(STORAGE_KEY);
			return data ? JSON.parse(data) : {};
		} catch (e) {
			return {};
		}
	}

	/**
	 * Guarda un valor individual en localStorage.
	 */
	function saveField(fieldId, value) {
		var data = getSavedData();
		data[fieldId] = value;
		try {
			localStorage.setItem(STORAGE_KEY, JSON.stringify(data));
		} catch (e) {
			// localStorage lleno o no disponible; falla silenciosamente
		}
	}

	/**
	 * Restaura los valores guardados a los campos vacíos.
	 */
	function restoreFields() {
		var data = getSavedData();

		fieldIds.forEach(function (id) {
			if (!data[id]) return;

			var field = document.getElementById(id);
			if (!field) return;

			// Solo restaurar si el campo está vacío
			if (!field.value.trim()) {
				field.value = data[id];
				// Disparar evento de cambio para que WooCommerce actualice su estado
				field.dispatchEvent(new Event('change', { bubbles: true }));
				field.dispatchEvent(new Event('input', { bubbles: true }));
			}
		});
	}

	/**
	 * Vincula los listeners de guardado a cada campo.
	 */
	function bindSaveListeners() {
		fieldIds.forEach(function (id) {
			var field = document.getElementById(id);
			if (!field) return;

			field.addEventListener('change', function () {
				saveField(id, this.value);
			});

			// También guardar mientras escribe para no perder datos
			field.addEventListener('input', function () {
				saveField(id, this.value);
			});
		});
	}

	/**
	 * Limpia los datos guardados después de un pedido exitoso.
	 */
	function clearOnOrderComplete() {
		// WooCommerce redirige a la página de "pedido recibido" tras completar
		if (document.querySelector('.woocommerce-order-received') ||
			document.querySelector('.woocommerce-thankyou-order-received')) {
			try {
				localStorage.removeItem(STORAGE_KEY);
			} catch (e) {
				// Falla silenciosamente
			}
		}
	}

	/**
	 * Inicializa el script.
	 */
	function init() {
		clearOnOrderComplete();
		restoreFields();
		bindSaveListeners();
	}

	// Ejecutar cuando el DOM esté listo
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}

	// WooCommerce puede recargar fragmentos del checkout vía AJAX,
	// así que re-vinculamos después de cada actualización.
	jQuery(document.body).on('updated_checkout', function () {
		restoreFields();
		bindSaveListeners();
	});
})();
