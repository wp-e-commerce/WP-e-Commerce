/* global sputnik */
window.sputnik = window.sputnik || {};

(function($){
	sputnik = function( attributes ) {
		//if ( media.view.MediaFrame.Post )
		//	return new media.view.MediaFrame.Post( attributes ).render().attach().open();
	};

	_.extend( sputnik, { model: {}, view: {}, controller: {} });

	// Link any localized strings.
	//l10n = media.model.l10n = _.isUndefined( _wpMediaModelsL10n ) ? {} : _wpMediaModelsL10n;

	_.extend( sputnik, {
		/**
		 * sputnik.template( id )
		 *
		 * Fetches a template by id.
		 *
		 * @param  {string} id   A string that corresponds to a DOM element with an id prefixed with "tmpl-".
		 *                       For example, "attachment" maps to "tmpl-attachment".
		 * @return {function}    A function that lazily-compiles the template requested.
		 */
		template: _.memoize( function( id ) {
			var compiled,
				options = {
					evaluate:    /<#([\s\S]+?)#>/g,
					interpolate: /\{\{\{([\s\S]+?)\}\}\}/g,
					escape:      /\{\{([\s\S]+?)\}\}/g
				};

			return function( data ) {
				compiled = compiled || _.template( $( '#tmpl-' + id ).html(), null, options );
				return compiled( data );
			};
		})
	});

	sputnik.controller.Region = function( options ) {
		_.extend( this, _.pick( options || {}, 'id', 'controller' ) );

		this.on( 'activate:empty', this.empty, this );
		this.mode('empty');
	};

	// Use Backbone's self-propagating `extend` inheritance method.
	sputnik.controller.Region.extend = Backbone.Model.extend;

	_.extend( sputnik.controller.Region.prototype, Backbone.Events, {
		trigger: (function() {
			var eventSplitter = /\s+/,
				trigger = Backbone.Events.trigger;

			return function( events ) {
				var mode = ':' + this._mode,
					modeEvents = events.split( eventSplitter ).join( mode ) + mode;

				trigger.apply( this, arguments );
				trigger.apply( this, [ modeEvents ].concat( _.rest( arguments ) ) );
				return this;
			};
		}()),

		mode: function( mode ) {
			if ( mode ) {
				this.trigger('deactivate');
				this._mode = mode;
				return this.trigger('activate');
			}
			return this._mode;
		},

		view: function( view ) {
			var previous = this._view,
				mode = this._mode,
				id = this.id;

			// If no argument is provided, return the current view.
			if ( ! view )
				return previous;

			// If we're attempting to switch to the current view, bail.
			if ( view === previous )
				return;

			// Add classes to the new view.
			if ( id )
				view.$el.addClass( 'region-' + id );

			if ( mode )
				view.$el.addClass( 'mode-' + mode );

			// Remove the hide class.
			// this.$el.removeClass( 'hide-' + subview );

			if ( previous ) {
				// Replace the view in place.
				previous.$el.replaceWith( view.$el );

				// Fire the view's `destroy` event if it exists.
				if ( previous.destroy )
					previous.destroy();
				// Undelegate events.
				previous.undelegateEvents();
			}

			this._view = view;
		},

		empty: function() {
			this.view( new Backbone.View() );
		}
	});

	sputnik.model.Plugin = Backbone.Model.extend({
		defaults: {
			title: '',
			version: '',
			tested: '',
			requires: '',
			rating: 0,
			user_rating: 0
		}
	})

	/*sputnik.model.Plugin = Backbone.Model.extend({
		sync: function( method, model, options ) {
			if ( 'read' === method ) {
				options = options || {};
				options.context = this;
				options.data = _.extend( options.data || {}, {
					action: 'sputnik-get-plugin',
					id: this.id
				});
				return media.ajax( options );
			}
		},

		parse: function( resp, xhr ) {
			if ( ! resp )
				return resp;

			// Convert date strings into Date objects.
			resp.date = new Date( resp.date );
			resp.modified = new Date( resp.modified );
			return resp;
		}
	}, {
		create: function( attrs ) {
			return Attachments.all.push( attrs );
		},

		get: _.memoize( function( id, attachment ) {
			return Attachments.all.push( attachment || { id: id } );
		})
	});*/


	/**
	 * wp.media.view.Frame
	 */
	sputnik.view.Frame = Backbone.View.extend({
		className: 'sputnik-frame',
		regions:   ['menu', 'content'],

		initialize: function() {
			this._createRegions();
			this._createStates();

			_.defaults( this.options, {
				title:    '',
				modal:    true
			});

			// Initialize modal container view.
			if ( this.options.modal ) {
				this.modal = new sputnik.view.Modal({
					controller: this,
					$content:   this.$el,
					title:      this.options.title
				});
			}
		},

		_createRegions: function() {
			// Clone the regions array.
			this.regions = this.regions ? this.regions.slice() : [];

			// Initialize regions.
			_.each( this.regions, function( region ) {
				this[ region ] = new sputnik.controller.Region({
					controller: this,
					id:         region
				});
			}, this );
		},

		_createStates: function() {
			// Create the default `states` collection.
			this.states = new Backbone.Collection();

			// Ensure states have a reference to the frame.
			this.states.on( 'add', function( model ) {
				model.frame = this;
			}, this );
		},

		render: function() {
			if ( this.modal )
				this.modal.render();

			var els = _.map( this.regions, function( region ) {
					return this[ region ].view().el;
				}, this );

			// Detach the current views to maintain event bindings.
			$( els ).detach();
			this.$el.html( els );

			return this;
		},

		reset: function() {
			this.states.invoke( 'trigger', 'reset' );
		}
	});

	// Make the `Frame` a `StateMachine`.
	//_.extend( media.view.Frame.prototype, media.controller.StateMachine.prototype );

	// Map some of the modal's methods to the frame.
	_.each(['open','close','attach','detach'], function( method ) {
		sputnik.view.Frame.prototype[ method ] = function( view ) {
			if ( this.modal )
				this.modal[ method ].apply( this.modal, arguments );
			return this;
		};
	});

	sputnik.view.Modal = Backbone.View.extend({
		tagName:  'div',
		template: sputnik.template('sputnik-modal'),

		events: {
			'click .sputnik-modal-backdrop, .sputnik-modal-close' : 'closeHandler'
		},

		initialize: function() {
			//this.controller = this.options.controller;

			_.defaults( this.options, {
				container: document.body,
				title:     ''
			});
		},

		render: function() {
			// Ensure content div exists.
			this.options.$content = this.options.$content || $('<div />');

			// Detach the content element from the DOM to prevent
			// `this.$el.html()` from garbage collecting its events.
			this.options.$content.detach();

			this.$el.html( this.template({
				title: this.options.title
			}) );

			this.options.$content.addClass('sputnik-modal-content');
			this.$('.sputnik-modal').append( this.options.$content );
			return this;
		},

		attach: function() {
			this.$el.appendTo( this.options.container );
			//this.controller.trigger( 'attach', this.controller );
			return this;
		},

		detach: function() {
			this.$el.detach();
			//this.controller.trigger( 'detach', this.controller );
			return this;
		},

		open: function() {
			this.$el.show();
			//this.controller.trigger( 'open', this.controller );
			return this;
		},

		close: function() {
			this.$el.hide();
			//this.controller.trigger( 'close', this.controller );
			return this;
		},

		closeHandler: function( event ) {
			event.preventDefault();
			this.close();
		},

		content: function( $content ) {
			// Detach any existing content to prevent events from being lost.
			if ( this.options.$content )
				this.options.$content.detach();

			// Set and render the content.
			this.options.$content = ( $content instanceof Backbone.View ) ? $content.$el : $content;
			return this.render();
		}
	});
})(jQuery);