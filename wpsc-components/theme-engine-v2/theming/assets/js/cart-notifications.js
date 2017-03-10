/**
 * WP eCommerce - v4.0.0 - 2017-03-10
 * https://wpecommerce.org/
 *
 * Copyright (c) 2017;
 * Licensed GPLv2+
 */

(function e(t,n,r){function s(o,u){if(!n[o]){if(!t[o]){var a=typeof require=="function"&&require;if(!u&&a)return a(o,!0);if(i)return i(o,!0);var f=new Error("Cannot find module '"+o+"'");throw f.code="MODULE_NOT_FOUND",f}var l=n[o]={exports:{}};t[o][0].call(l.exports,function(e){var n=t[o][1][e];return s(n?n:e)},l,l.exports,e,t,n,r)}return n[o].exports}var i=typeof require=="function"&&require;for(var o=0;o<r.length;o++)s(r[o]);return s})({1:[function(require,module,exports){
'use strict';

(function (window, document, $, notifs, undefined) {
	'use strict';

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
		Product: require('./models/product.js')(notifs.currency, notifs.ajaxurl, notifs.baseRoute),
		Status: require('./models/status.js')(notifs.currency, notifs.strings)
	};

	notifs.collections = {
		Products: require('./collections/products.js')(notifs.currency, notifs.models.Product)
	};

	notifs.views = {
		ProductRow: require('./views/product-row.js')(log)
	};

	notifs.views.Cart = require('./views/cart.js')({
		currency: notifs.currency,
		statusModel: notifs.models.Status,
		initialStatus: notifs.CartView.status,
		rowView: notifs.views.ProductRow
	}, $id, log);

	notifs.init = function () {
		$(document.body).on('click', '.wpsc-add-to-cart', notifs.clickAddProductToCart)
		// .on( 'submit', '.wpsc-add-to-cart-form', notifs.clickAddProductToCart )
		.on('click', '#wpsc-modal-overlay', notifs.closeModal).append($id('tmpl-wpsc-modal').html());

		// Kick it off.
		notifs.CartView = new notifs.views.Cart({
			collection: new notifs.collections.Products(notifs.CartView.items)
		});
	};

	notifs.clickAddProductToCart = function (evt) {
		evt.preventDefault();
		var $product = $(this).parents('.wpsc-product');
		notifs.addProductToCart($product);
	};

	notifs.addProductToCart = function ($product) {
		// Experimental.
		// TODO: Replace dom-to-model with actual localized JSON model data.
		notifs.domToModel = notifs.domToModel || require('./utils/product-dom-to-model.js')(notifs.currency);
		notifs.CartView.trigger('add-to-cart', notifs.domToModel.prepare($product));
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

module.exports = function (currency, prodouctModel) {
	return Backbone.Collection.extend({
		model: prodouctModel,

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

module.exports = function (currency, ajaxurl, baseRoute) {
	return Backbone.Model.extend({
		defaults: {
			id: 0,
			url: '',
			price: '',
			formattedPrice: '',
			title: '',
			thumb: '',
			quantity: 0,
			remove_url: '',
			variations: [],
			action: ''
		},

		initialize: function initialize() {
			this.listenTo(this, 'add remove', this.sync);
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
					value = currency.format(this.get('price'));
					break;

				case 'variations':
					break;

				default:
					value = value.trim();
					break;
			}

			return value;
		},

		url: function url() {
			var url = baseRoute + encodeURIComponent(this.get('id')) + '?_wp_nonce=' + encodeURIComponent(this.get('nonce'));

			// switch( this.get( 'action' ) ) {

			// 	case 'edit':
			// 		url += '&action=edit&quantity=' + this.get( 'quantity' );
			// 		break;

			// 	default:
			// 		url += '&action=' + this.get( 'action' );
			// 		break;
			// }

			return url;
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
		prepare: function prepare($product) {
			var $productForm = $product.find('.wpsc-add-to-cart-form');
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
				quantity: $product.find('[name="quantity"]').val(),
				remove_url: '',
				variations: getVariationsFromProductForm($productForm)
			};
		}
	};
};

},{}],7:[function(require,module,exports){
'use strict';

module.exports = function (args, $, log) {
	return Backbone.View.extend({
		el: '#wpsc-cart-notification',
		template: wp.template('wpsc-modal-inner'),
		status: {},
		events: {
			'click .wpsc-close-modal': 'clickClose',
			'click .wpsc-cart-view-toggle i': 'clickToggleView'
		},

		initialize: function initialize() {
			this.$overlay = $('wpsc-modal-overlay');

			this.status = new args.statusModel(args.initialStatus);
			this.status.set('numberItems', this.collection.length);
			this.status.set('numberChanged', this.collection.length);

			this.listenTo(this.collection, 'remove', this.checkEmpty);
			this.listenTo(this.collection, 'add remove', this.updateStatusandRender);
			this.listenTo(this.collection, 'render', this.render);
			this.listenTo(this.collection, 'change', this.render);
			this.listenTo(this.collection, 'error', this.handleError);
			// this.listenTo( this.collection, 'sync', this.didSync );

			this.listenTo(this.status, 'change', this.maybeUpdateView);
			this.listenTo(this, 'open', this.renderNoAction);
			this.listenTo(this, 'close', this.close);
			this.listenTo(this, 'add-to-cart', this.maybeAdd);

			// this.render();
			this.renderNoShow();
		},

		// didSync: function( didSync ) {
		// 	// log( 'Collection didSync', didSync );
		// },

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

			return this;
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
			this.status.set('total', this.collection.totalPrice());
		},

		maybeAdd: function maybeAdd(data) {
			var model = this.collection.getById(data.id);

			if (model) {
				var qty = Math.round(parseInt(model.get('quantity'), 10) + parseInt(data.quantity, 10));

				// Update quantity.
				model.set('quantity', qty);

				this.status.set('total', this.collection.totalPrice());
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

module.exports = function (log) {
	return Backbone.View.extend({
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
			// 'click .wpsc-cart-item-edit' : 'edit'
			'click .wpsc-cart-item-remove': 'removeIt'
		},

		initialize: function initialize() {
			this.listenTo(this, 'change', this.maybeRender);
			this.listenTo(this, 'sync', this.didSync);
			this.listenTo(this, 'error', this.handleError);
		},

		handleError: function handleError(errorObject) {
			log('Model handleError', errorObject);
		},

		didSync: function didSync(_didSync) {
			log('Model didSync', _didSync);
		},

		// Render the row
		maybeRender: function maybeRender(changedModel) {
			if (changedModel.changed.quantity) {
				return;
			}
			this.render();
		},

		// Render the row
		render: function render() {
			this.$el.html(this.template(this.model.toJSON()));
			return this;
		},

		edit: function edit(e) {
			e.preventDefault();

			// TODO: Show quantity input.
		},

		// Perform the Removal
		removeIt: function removeIt(e) {
			e.preventDefault();
			var _this = this;

			// Ajax error handler
			var destroyError = function destroyError(model, response) {
				log('destroyError', response);

				// for now:
				_this.$el.remove();
				// whoops.. re-show row and add error message
				// _this.$el.fadeIn( 300 );
			};

			// Ajax success handler
			var destroySuccess = function destroySuccess(model, response) {
				// If our response reports success
				if (response.success) {
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
		}
	});
};

},{}]},{},[1]);
