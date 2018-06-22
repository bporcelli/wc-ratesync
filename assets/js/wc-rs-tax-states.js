/* global jQuery, taxStatesLocalizeScript, wp */
( function( $, data, wp ) {
    $( function() {
        var $tbody          = $( '.wc-rs-tax-state-rows' ),
            $row_template   = wp.template( 'wc-rs-tax-state-row' ),
            $blank_template = wp.template( 'wc-rs-tax-state-row-blank' ),

            // Backbone model
            TaxStates       = Backbone.Model.extend( {
                states: []
            } ),

            // Backbone view
            TaxStatesView = Backbone.View.extend({
                rowTemplate: $row_template,
                initialize: function() {
                    this.listenTo( this.model, 'saved:states', this.render );

                    $( document.body ).on( 'click', '.wc-rs-add-tax-state', { view: this }, this.onAddState );
                    $( document.body ).on( 'wc_backbone_modal_response', this.onAddStateSubmitted );

                    $( document.body ).on( 'click', '.select_all', function() {
                        $( this ).closest( 'form' ).find( 'select option' ).attr( 'selected', 'selected' );
                        $( this ).closest( 'form' ).find( 'select' ).trigger( 'change' );
                        return false;
                    });
                    $( document.body ).on( 'click', '.select_none', function() {
                        $( this ).closest( 'form' ).find( 'select option' ).removeAttr( 'selected' );
                        $( this ).closest( 'form' ).find( 'select' ).trigger( 'change' );
                        return false;
                    });
                },
                block: function() {
                    $( this.el ).block({
                        message: null,
                        overlayCSS: {
                            background: '#fff',
                            opacity: 0.6
                        }
                    });
                },
                unblock: function() {
                    $( this.el ).unblock();
                },
                render: function() {
                    var states = _.indexBy( this.model.get( 'states' ), 'abbrev' ),
                        view   = this;

                    // Blank out the contents.
                    this.$el.empty();
                    this.unblock();

                    if ( _.size( states ) ) {
                        // Populate $tbody with the current states
                        $.each( states, function( abbrev, rowData ) {
                            if ( 'yes' === rowData.shipping_taxable ) {
                                rowData.shipping_tax_icon = '<span class="woocommerce-input-toggle woocommerce-input-toggle--enabled">' + data.strings.yes + '</span>';
                            } else {
                                rowData.shipping_tax_icon = '<span class="woocommerce-input-toggle woocommerce-input-toggle--disabled">' + data.strings.no + '</span>';
                            }

                            // Add strings for internationalization
                            rowData.strings = data.strings;

                            view.$el.append( view.rowTemplate( rowData ) );
                        } );

                        // Make the rows function
                        this.$el.find( '.wc-rs-tax-state-delete' ).on( 'click', { view: this }, this.onDeleteRow );
                        this.$el.find( '.wc-rs-shipping-tax-enabled a').on( 'click', { view: this }, this.onToggleEnabled );
                    } else {
                        view.$el.append( $blank_template( { strings: data.strings } ) );
                    }
                },
                onDeleteRow: function( event ) {
                    var view         = event.data.view,
                        model        = view.model,
                        states       = _.indexBy( model.get( 'states' ), 'abbrev' ),
                        state_abbrev = $( this ).closest('tr').data( 'abbrev' );

                    event.preventDefault();

                    delete states[ state_abbrev ];
                    model.set( 'states', states );
                    view.render();
                },
                onToggleEnabled: function( event ) {
                    var view         = event.data.view,
                        $target      = $( event.target ),
                        model        = view.model,
                        states       = _.indexBy( model.get( 'states' ), 'abbrev' ),
                        state_abbrev = $target.closest( 'tr' ).data( 'abbrev' ),
                        enabled      = $target.closest( 'tr' ).data( 'enabled' ) === 'yes' ? 'no' : 'yes';

                    event.preventDefault();
                    states[ state_abbrev ].shipping_taxable = enabled;
                    model.set( 'states', states );
                    view.render();
                },
                onAddState: function( event ) {
                    var view       = event.data.view,
                        model      = view.model,
                        states     = _.indexBy( model.get( 'states' ), 'abbrev' ),
                        all_states = data.all_states;

                    event.preventDefault();

                    var available = _.filter( all_states, function( state ) {
                        return ! ( state[ 'abbrev' ] in states );
                    } );

                    $( this ).WCBackboneModal( {
                        template : 'wc-rs-modal-add-tax-state',
                        variable : {
                            states  : available,
                            strings : data.strings
                        }
                    } );

                    $( document.body ).trigger( 'wc-enhanced-select-init' );
                },
                onAddStateSubmitted: function( event, target, posted_data ) {
                    if ( 'wc-rs-modal-add-tax-state' !== target ) {
                        return;
                    }

                    var view       = taxStatesView,
                        model      = view.model,
                        states     = _.indexBy( model.get( 'states' ), 'abbrev' ),
                        new_states = posted_data[ 'wc_rs_tax_states' ],
                        all_states = data.all_states;

                    _.each( new_states, function( abbrev ) {
                        states[ abbrev ] = all_states[ abbrev ];
                    } );

                    taxStatesView.block();
                    taxStatesView.model.set( 'states', states );
                    taxStatesView.model.trigger( 'saved:states' );
                    taxStatesView.unblock();
                }
            } ),
            taxStates = new TaxStates( {
                states: data.tax_states
            } ),
            taxStatesView = new TaxStatesView({
                model:    taxStates,
                el:       $tbody
            } );

        taxStatesView.render();
    });
})( jQuery, taxStatesLocalizeScript, wp );
