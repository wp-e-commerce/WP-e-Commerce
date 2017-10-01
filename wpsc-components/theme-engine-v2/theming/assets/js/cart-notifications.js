/**
 * WP eCommerce - v4.0.0 - 2017-03-15
 * https://wpecommerce.org/
 *
 * Copyright (c) 2017;
 * Licensed GPLv2+
 */

(function e(t,n,r){function s(o,u){if(!n[o]){if(!t[o]){var a=typeof require=="function"&&require;if(!u&&a)return a(o,!0);if(i)return i(o,!0);var f=new Error("Cannot find module '"+o+"'");throw f.code="MODULE_NOT_FOUND",f}var l=n[o]={exports:{}};t[o][0].call(l.exports,function(e){var n=t[o][1][e];return s(n?n:e)},l,l.exports,e,t,n,r)}return n[o].exports}var i=typeof require=="function"&&require;for(var o=0;o<r.length;o++)s(r[o]);return s})({1:[function(require,module,exports){
'use strict';

(function (window, document, $, notifs, undefined) {
	'use strict';

	var ESCAPE = 27;

	var $id = function $id(id) {
		return $(document.getElementById(id));
	};

	var log = function log() {
		log.history = log.history || [];
		log.history.push(arguments);
		if (notifs.debug && window.console && window.console.log) {
			window.console.log(Array.prototype.slice.call(arguments));
		}
	};

	notifs.currency = require('./utils/currency.js')(notifs.currency);

	notifs.models = {
		Product: require('./models/product.js')(notifs),
		Status: require('./models/status.js')(notifs.currency, notifs.strings)
	};

	notifs.collections = {
		Products: require('./collections/products.js')(notifs)
	};

	notifs.views = {
		ProductRow: require('./views/product-row.js')(log, notifs)
	};

	notifs.views.Cart = require('./views/cart.js')({
		currency: notifs.currency,
		statusModel: notifs.models.Status,
		initialStatus: notifs.CartView.status,
		rowView: notifs.views.ProductRow
	}, $id, log);

	notifs.init = function () {
		$(document.body).on('click', '.wpsc-add-to-cart', notifs.clickAddProductToCart).on('click', '#wpsc-modal-overlay', notifs.closeModal).on('click', '#wpsc-view-cart-button', notifs.openModal).append($id('tmpl-wpsc-modal').html());

		$(document).on('keydown', function (evt) {
			if (ESCAPE === evt.which) {
				notifs.closeModal();
			}
		});

		// Kick it off.
		notifs.CartView = new notifs.views.Cart({
			collection: new notifs.collections.Products(notifs.CartView.items)
		});
	};

	notifs.clickAddProductToCart = function (evt) {
		var $productForm = null;
		var $this = $(this);
		var $product = $this.parents('.wpsc-product');
		evt.preventDefault();

		if (!$product.length) {
			$productForm = $this.parents('.wpsc-add-to-cart-form');
			if ($productForm.length) {
				$product = $id('product-' + $productForm.data('id'));
			}
		}

		if ($product.length) {
			notifs.addProductToCart($product, $productForm);
		}
	};

	notifs.addProductToCart = function ($product, $productForm) {
		// Experimental.
		// TODO: Replace dom-to-model with actual localized JSON model data.
		notifs.domToModel = notifs.domToModel || require('./utils/product-dom-to-model.js')(notifs.currency);
		notifs.CartView.trigger('add-to-cart', notifs.domToModel.prepare($product, $productForm));
	};

	notifs.closeModal = function () {
		notifs.CartView.trigger('close');
	};

	notifs.openModal = function () {
		notifs.CartView.trigger('open');
	};

	$(notifs.init);
})(window, document, jQuery, window.WPSC.cartNotifications);

},{"./collections/products.js":2,"./models/product.js":3,"./models/status.js":4,"./utils/currency.js":5,"./utils/product-dom-to-model.js":6,"./views/cart.js":7,"./views/product-row.js":8}],2:[function(require,module,exports){
'use strict';

module.exports = function (notifs) {
	return Backbone.Collection.extend({
		model: notifs.models.Product,
		url: notifs.baseRoute + '/cart',

		getById: function getById(id) {
			id = parseInt(id, 10);
			return this.find(function (model) {
				return model.get('id') === id;
			});
		},

		totalPrice: function totalPrice() {
			return this.reduce(function (memo, model) {
				return memo + model.getTotal();
			}, 0).toFixed(2);
		}
	});
};

},{}],3:[function(require,module,exports){
'use strict';

module.exports = function (notifs) {

	return Backbone.Model.extend({
		defaults: {
			id: 0,
			nonce: '',
			deleteNonce: '',
			url: '',
			price: '',
			formattedPrice: '',
			title: '',
			thumb: '',
			quantity: 0,
			remove_url: '',
			variations: [],
			editQty: false,
			action: ''
		},

		getTotal: function getTotal() {
			return this.get('price') * parseInt(this.get('quantity'), 10);
		},

		get: function get(attribute) {
			var value = Backbone.Model.prototype.get.call(this, attribute);

			switch (attribute) {
				case 'id':
				case 'quantity':
					value = parseInt(value, 10);
					break;

				case 'price':
					value = parseFloat(value).toFixed(2);
					break;

				case 'formattedPrice':
					value = notifs.currency.format(this.get('price'));
					break;

				case 'variations':
					break;

				case 'editQty':
					value = Boolean(value);
					break;

				default:
					value = value.trim();
					break;
			}

			return value;
		},

		sync: function sync(method, model, options) {
			var beforeSend;

			options = options || {};
			options.url = model.url();

			if ('update' === method) {
				if ('quantity' in model.changed) {
					options.url = this.addQueryVar(options.url, 'quantity', encodeURIComponent(this.get('quantity')));
				} else {
					options.url = model.collection.url;
					options.url += '/add/' + encodeURIComponent(this.get('id'));
				}
			}

			var nonce = 'delete' === method ? this.get('deleteNonce') : this.get('nonce');
			options.url = this.addQueryVar(options.url, '_wp_nonce', encodeURIComponent(nonce));

			if (!_.isUndefined(notifs.apiNonce) && !_.isNull(notifs.apiNonce)) {
				beforeSend = options.beforeSend;

				options.beforeSend = function (xhr) {
					xhr.setRequestHeader('X-WP-Nonce', notifs.apiNonce);

					if (beforeSend) {
						return beforeSend.apply(this, arguments);
					}
				};
			}

			// window.console.warn('method', method);
			// window.console.warn('model.changed', model.changed);
			// window.console.warn('options.url', options.url);
			return Backbone.sync(method, model, options);
		},

		addQueryVar: function addQueryVar(uri, key, value) {
			var re = new RegExp('([?&])' + key + '=.*?(&|$)', 'i');

			if (uri.match(re)) {
				return uri.replace(re, '$1' + key + '=' + value + '$2');
			}

			return uri + (-1 !== uri.indexOf('?') ? '&' : '?') + key + '=' + value;
		}
	});
};

},{}],4:[function(require,module,exports){
'use strict';

module.exports = function (currency, strings) {
	return Backbone.Model.extend({
		sync: function sync() {
			return false;
		},

		defaults: {
			status: 'closed',
			action: 'added',
			actionText: strings.status_added,
			actionIcon: 'wpsc-icon-check',
			countClass: '',
			view: 'expanded',
			total: 0,
			formattedTotal: 0,
			subTotal: 0,
			shippingTotal: 0,
			numberChanged: 1,
			numberItems: 0
		},

		_get: function _get(value, attribute) {
			var action;

			switch (attribute) {
				case 'countClass':
					value = 'none' === this.get('action') ? 'wpsc-hide' : this.defaults.countClass;
					break;

				case 'actionText':
					action = this.get('action');
					value = strings['status_' + action] ? strings['status_' + action] : this.defaults.actionText;
					break;

				case 'formattedTotal':
					value = currency.format(this.get('total'));
					break;

				case 'actionIcon':
					switch (this.get('action')) {
						case 'removed':
							value = 'wpsc-icon-remove-sign';
							break;

						case 'none':
							value = 'wpsc-hide';
							break;

						default:
							value = this.defaults.actionIcon;
					}
					break;
			}

			return value;
		},

		get: function get(attribute) {
			return this._get(Backbone.Model.prototype.get.call(this, attribute), attribute);
		},

		// hijack the toJSON method and overwrite the data that is sent back to the view.
		toJSON: function toJSON() {
			return _.mapObject(Backbone.Model.prototype.toJSON.call(this), _.bind(this._get, this));
		}
	});
};

},{}],5:[function(require,module,exports){
'use strict';

module.exports = function (l10n) {
	var currency = {
		l10n: l10n,
		template: false
	};

	currency.format = function (amt) {

		// Format the price for output
		amt = currency.numberFormat(amt, l10n.decimals, l10n.decimalSep, l10n.thousandsSep);

		if (!currency.template) {
			currency.template = wp.template('wpsc-currency-format'); // #tmpl-wpsc-currency-format
		}

		return currency.template({
			'code': l10n.code,
			'symbol': l10n.symbol,
			'amount': amt
		}).trim();
	};

	currency.deformat = function (formatted) {
		var amount = formatted.replace(l10n.decimalSep, '.').replace('-', '').replace(l10n.thousandsSep, '').replace(l10n.code, '').replace(l10n.symbol, '');

		return parseFloat(amount).toFixed(2);
	};

	// http://locutus.io/php/number_format/
	currency.numberFormat = function (number, decimals, decSep, thouSep) {

		number = (number + '').replace(/[^0-9+\-Ee.]/g, '');
		var n = !isFinite(+number) ? 0 : +number;
		var prec = !isFinite(+decimals) ? 0 : Math.abs(decimals);
		var sep = typeof thouSep === 'undefined' ? ',' : thouSep;
		var dec = typeof decSep === 'undefined' ? '.' : decSep;
		var s = '';

		var toFixedFix = function toFixedFix(n, prec) {
			var k = Math.pow(10, prec);
			return '' + (Math.round(n * k) / k).toFixed(prec);
		};

		// @todo: for IE parseFloat(0.55).toFixed(0) = 0;
		s = (prec ? toFixedFix(n, prec) : '' + Math.round(n)).split('.');
		if (s[0].length > 3) {
			s[0] = s[0].replace(/\B(?=(?:\d{3})+(?!\d))/g, sep);
		}
		if ((s[1] || '').length < prec) {
			s[1] = s[1] || '';
			s[1] += new Array(prec - s[1].length + 1).join('0');
		}

		return s.join(dec);
	};

	return currency;
};

},{}],6:[function(require,module,exports){
'use strict';

module.exports = function (currency) {

	var getVariationsFromProductForm = function getVariationsFromProductForm($productForm) {
		var $variations = $productForm.find('[name^="wpsc_product_variations"]');
		var variations = [];

		if (!$variations.length) {
			return variations;
		}

		$variations.each(function () {
			var $variation = jQuery(this);
			var $variationLabel = $productForm.find('label[for="' + $variation.attr('id') + '"]');
			var variationValue, $selected, $selectedLabel;

			switch ($variation[0].tagName) {
				case 'RADIO':
					$selected = $variation.find('[value="' + $variation.val() + '"]');
					$selectedLabel = $selected.length ? $productForm.find('label[for="' + $selected.attr('id') + '"]').text() : [];
					if ($selectedLabel.length) {
						variationValue = $selectedLabel.text();
					}
					break;

				case 'SELECT':
					$selected = $variation.find('[value="' + $variation.val() + '"]');
					if ($selected.length) {
						variationValue = $selected.text();
					}
					break;

				default:
					variationValue = $variation.val();
					break;
			}

			variations.push({
				label: $variationLabel.length ? $variationLabel.text() : '',
				value: variationValue
			});
		});

		return variations;
	};

	return {
		prepare: function prepare($product, $productForm) {
			$productForm = $productForm && $productForm.length ? $productForm : $product.find('.wpsc-add-to-cart-form');
			var nonce = $productForm.find('[name="_wp_nonce"]').val();
			var $thumb = $product.find('.wpsc-product-thumbnail');
			var $salePrice = $product.find('.wpsc-product-price .wpsc-sale-price .wpsc-amount');
			var price = $salePrice.length ? $salePrice.text() : $product.find('.wpsc-product-price .wpsc-amount').last().text();

			return {
				id: $productForm.data('id'),
				nonce: nonce,
				url: $thumb.length ? $thumb.attr('href') : $product.find('.wpsc-product-title > a').attr('href'),
				price: currency.deformat(price),
				formattedPrice: price,
				title: $product.find('.wpsc-product-title > a').text(),
				thumb: $thumb.length ? $thumb.html() : '',
				quantity: $productForm.find('[name="quantity"]').val(),
				remove_url: '',
				variations: getVariationsFromProductForm($productForm)
			};
		}
	};
};

},{}],7:[function(require,module,exports){
'use strict';

module.exports = function (args, $id, log) {
	return Backbone.View.extend({
		el: '#wpsc-cart-notification',
		$btn: '',
		$overlay: '',
		template: wp.template('wpsc-modal-inner'),
		status: {},
		events: {
			'click .wpsc-close-modal': 'clickClose',
			'click .wpsc-cart-view-toggle i': 'clickToggleView'
		},

		initialize: function initialize() {
			this.$btn = $id('wpsc-view-cart-button');
			this.$overlay = $id('wpsc-modal-overlay');

			this.status = new args.statusModel(args.initialStatus);
			this.status.set('numberItems', this.collection.length);
			this.status.set('numberChanged', this.collection.length);

			this.listenTo(this.collection, 'remove', this.checkEmpty);
			this.listenTo(this.collection, 'add remove', this.updateStatusandRender);
			this.listenTo(this.collection, 'render', this.render);
			this.listenTo(this.collection, 'change:quantity', this.setTotal);
			this.listenTo(this.collection, 'change:editQty', this.calculateHeight);
			this.listenTo(this.collection, 'error', this.handleError);

			this.listenTo(this.status, 'change', this.maybeUpdateView);
			this.listenTo(this, 'open', this.renderNoAction);
			this.listenTo(this, 'close', this.close);
			this.listenTo(this, 'add-to-cart', this.maybeAdd);

			this.renderNoShow();
		},

		handleError: function handleError(errorObject) {
			log('Collection handleError', errorObject);
		},

		renderNoAction: function renderNoAction() {
			this.status.set('action', 'none');
			this.render();
		},

		renderNoShow: function renderNoShow() {

			this.$el
			// Update cart HTML
			.html(this.template(this.status.toJSON()))
			// Then insert the products node.
			.find('.wpsc-cart-body').html(this._getProducts());

			return this;
		},

		render: function render() {
			this.renderNoShow();
			this.$overlay.removeClass('wpsc-hide');
			this.status.set('status', 'open');

			this.calculateHeight();

			return this;
		},

		calculateHeight: function calculateHeight() {
			// Now that it's open, calculate it's inner height...
			var newHeight = this.$el.removeClass('wpsc-hide').removeClass('wpsc-cart-set-height').find('.wpsc-cart-notification-inner').outerHeight();

			// Do some calculation to make sure we don't go over 70% of the height of window.
			var winHeight = jQuery(window).height();
			var maxHeight = winHeight - winHeight * 0.3;

			if (newHeight > maxHeight) {
				newHeight = maxHeight;
				this.$el.addClass('wpsc-overflow');
			} else {
				this.$el.removeClass('wpsc-overflow');
			}

			// And set the height of the modal to match.
			this.$el.height(Math.round(newHeight)).addClass('wpsc-cart-set-height');
		},

		_getProducts: function _getProducts() {
			var productNodes = document.createDocumentFragment();

			// create a sub view for every model in the collection
			this.collection.each(function (model) {
				var row = new args.rowView({ model: model });
				productNodes.appendChild(row.render().el);
			});

			return productNodes;
		},

		checkEmpty: function checkEmpty() {
			if (!this.collection.length) {
				this.close();
			}
		},

		maybeUpdateView: function maybeUpdateView(statusChanged) {
			if (this.collection.length > 0 && (statusChanged.changed.view || statusChanged.changed.total)) {
				this.render();
			}
		},

		close: function close() {
			this.status.set('status', 'closed');
			this.collection.trigger('closeModal');
			this.$overlay.addClass('wpsc-hide');
			this.$el.addClass('wpsc-hide');
		},

		updateStatusandRender: function updateStatusandRender() {
			var prevNumber = this.status.get('numberItems');
			var numberChanged = this.collection.length - prevNumber;

			this.status.set('action', numberChanged < 0 ? 'removed' : 'added');

			numberChanged = Math.abs(numberChanged);
			numberChanged = numberChanged < 1 ? 1 : numberChanged;

			this.status.set('numberChanged', numberChanged);
			this.status.set('numberItems', this.collection.length);
			this.setTotal();

			this.$btn[this.collection.length ? 'removeClass' : 'addClass']('wpsc-hide');
		},

		setTotal: function setTotal() {
			this.status.set('total', this.collection.totalPrice());
		},

		maybeAdd: function maybeAdd(data) {
			var model = this.collection.getById(data.id);

			if (model) {
				var qty = Math.round(parseInt(model.get('quantity'), 10) + parseInt(data.quantity, 10));

				// Update quantity.
				model.set('quantity', qty);
				model.sync('update', model);

				this.setTotal();
			} else {
				model = this.collection.create(data);
			}

			return model;
		},

		clickClose: function clickClose(evt) {
			evt.preventDefault();
			this.close();
		},

		clickToggleView: function clickToggleView(evt) {
			evt.preventDefault();

			// Set the view state.
			this.status.set('view', jQuery(evt.target).data('view'));
		}

	});
};

},{}],8:[function(require,module,exports){
'use strict';

module.exports = function (log, notifs) {
	return Backbone.View.extend({
		$quantity: null,
		template: wp.template('wpsc-modal-product'),

		tagName: 'div',

		id: function id() {
			return 'wpsc-modal-cart-item-' + this.model.get('id');
		},

		className: function className() {
			var thumbClass = this.model.get('thumb') ? '' : 'no-';
			var id = this.model.get('id');
			return 'wpsc-cart-item wpsc-cart-item-' + id + ' wpsc-cart-' + thumbClass + 'thumb';
		},

		// Attach events
		events: {
			'click .wpsc-cart-item-edit': 'editQty',
			'click .wpsc-cart-item-remove': 'maybeRemoveIt',
			'click .wpsc-qty-button': 'modifyQty',
			'change .modify-cart-quantity': 'quantityChanged'
		},

		initialize: function initialize() {
			// this.listenTo( this, 'change', function( changedModel ) {
			// 	window.console.log( 'changedModel.changed', changedModel.changed );
			// } );

			this.listenTo(this.model, 'change:price', this.render);
			this.listenTo(this.model, 'change:formattedPrice', this.render);
			this.listenTo(this.model, 'change:editQty', this.render);
			this.listenTo(this.model.collection, 'closeModal', this.hideQty);

			this.listenTo(this, 'error', this.handleError);
		},

		handleError: function handleError(errorObject) {
			log('Model handleError', errorObject);
		},

		// Render the row
		render: function render() {
			this.$el.html(this.template(this.model.toJSON()));
			return this;
		},

		editQty: function editQty(evt) {
			evt.preventDefault();
			var editQty = this.model.get('editQty');
			this.model.set('editQty', !editQty);
		},

		hideQty: function hideQty() {
			this.model.set('editQty', false);
		},

		quantityChanged: function quantityChanged(evt) {
			var $input = this.$('.modify-cart-quantity');
			var qty = $input.val();

			if (qty < 1) {
				// Check if they meant to remove the item.
				if (this.maybeRemoveIt(evt)) {
					return;
				}
				// Ok, that was an oops, so keep the item around.
				qty = 1;
				$input.val(qty);
			}

			// Update cart item quantity.
			this.model.save({ quantity: qty });
		},

		// Perform the Removal
		maybeRemoveIt: function maybeRemoveIt(evt) {
			if (window.confirm(notifs.strings.sure_remove)) {
				this.removeIt(evt);
				return true;
			}

			return false;
		},

		// Perform the Removal
		removeIt: function removeIt(evt) {
			evt.preventDefault();
			var _this = this;

			// Ajax error handler
			var destroyError = function destroyError(model, response) {
				log('destroyError', response);

				// whoops.. re-show row and add error message
				_this.$el.fadeIn(300);
			};

			// Ajax success handler
			var destroySuccess = function destroySuccess(model, response) {
				// If our response reports success
				if (response.id) {
					log('destroySuccess', response);

					// remove our row completely
					_this.$el.remove();
				} else {
					// whoops, error
					destroyError(model, response);
				}
			};

			// Hide error message (if it's showing)
			// Optimistically hide row
			_this.$el.fadeOut(300);

			// Remove model and fire ajax event
			this.model.destroy({ success: destroySuccess, error: destroyError, wait: true });

			return true;
		},

		modifyQty: function modifyQty(evt) {
			var $button = this.$(evt.currentTarget);
			var $input = $button.parent().find('input');
			var oldVal = parseInt($input.val(), 10);
			var newVal = oldVal + 1;

			if ('-' === $button.text()) {
				// Don't allow decrementing below zero
				if (oldVal > 0) {
					newVal = oldVal - 1;
				} else {
					newVal = 0;
				}
			}

			$input.val(newVal).trigger('change');
		}

	});
};

},{}]},{},[1]);
