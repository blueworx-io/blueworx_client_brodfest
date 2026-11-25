/**
 * The pieces every Brodfest section block's edit script is built from.
 *
 * Plain JavaScript on the React WordPress already loads: no build step. Each
 * block's edit.js lays out its own fields with these. Nothing here decides how
 * a section looks on the site: the server draws that, from render.php.
 *
 * Controls: text (single line or several), a choice, a switch, an image with
 * its alt text (or "decorative"), and a list with a smallest and largest size.
 * Every section also gets a "Show this section on the site" switch in the block
 * tab of the right-hand panel; it is the one thing about a section's place on
 * the page that someone on a locked page may change.
 */
( function () {
	'use strict';

	var el = wp.element.createElement;
	var __ = wp.i18n.__;
	var c = wp.components;
	var be = wp.blockEditor;
	var D = 'blueworx-client-brodfest';
	// Set in full, so the editor's own page styles never size them.
	var LABEL = { margin: '0 0 4px', fontSize: '11px', fontWeight: 500, lineHeight: 1.4, textTransform: 'uppercase' };
	var cfg = window.brodfestBlocks || { pluginUrl: '', pluginToken: 'plugin:' };

	/** The address of an image, turning the plugin's own `plugin:` paths into one. */
	function resolve( src ) {
		if ( ! src ) {
			return '';
		}
		return src.indexOf( cfg.pluginToken ) === 0 ? cfg.pluginUrl + src.slice( cfg.pluginToken.length ).replace( /^\//, '' ) : src;
	}

	// Whether the section being drawn is hidden: set by the wrapper in register()
	// just before it draws the block, read by section() in the same pass.
	var drawingHidden = false;

	/** The frame every section sits in on the editor canvas. Call from edit. */
	function section( title, hint, children ) {
		var hidden = drawingHidden;
		var blockProps = be.useBlockProps( {
			style: { border: '1px dashed #8c8f94', padding: '16px', background: '#fff', color: '#1d2327', opacity: hidden ? 0.55 : 1 },
		} );
		return el(
			'div',
			blockProps,
			hidden ? el( 'p', { style: { margin: '0 0 8px', fontSize: '12px', fontWeight: 700, color: '#b32d2e' } }, __( 'Hidden: visitors do not see this section.', D ) ) : null,
			el( 'p', { style: { margin: '0 0 4px', fontSize: '12px', fontWeight: 700, letterSpacing: '0.06em', textTransform: 'uppercase' } }, title ),
			hint ? el( 'p', { style: { margin: '0 0 12px', fontSize: '12px', color: '#646970' } }, hint ) : null,
			children
		);
	}

	function text( label, value, onChange, opts ) {
		opts = opts || {};
		var Control = opts.multiline ? c.TextareaControl : c.TextControl;
		return el( Control, {
			__nextHasNoMarginBottom: true,
			__next40pxDefaultSize: ! opts.multiline,
			label: label,
			value: value || '',
			help: opts.help,
			type: opts.type,
			rows: opts.rows || 4,
			placeholder: opts.placeholder,
			onChange: onChange,
		} );
	}

	function choice( label, value, options, onChange ) {
		return el( c.SelectControl, {
			__nextHasNoMarginBottom: true,
			__next40pxDefaultSize: true,
			label: label,
			value: value,
			options: options,
			onChange: onChange,
		} );
	}

	function toggle( label, value, onChange, help ) {
		return el( c.ToggleControl, { __nextHasNoMarginBottom: true, label: label, checked: !! value, help: help, onChange: onChange } );
	}

	/**
	 * An image with its alt text, or a "decorative" switch.
	 *
	 * @param {string}   label    The field's name.
	 * @param {Object}   value    Where the current values are read from (the
	 *                            block's attributes, or one list item).
	 * @param {Function} onChange Called with an object of attributes to change.
	 * @param {Object}   map      Names of the attributes: url, id and, when the
	 *                            image has its own alt text, alt and decorative.
	 */
	function image( label, value, onChange, map ) {
		var src = resolve( value[ map.url ] );
		var decorative = map.decorative ? !! value[ map.decorative ] : false;

		function patch( url, id, alt ) {
			var p = {};
			p[ map.url ] = url;
			if ( map.id ) {
				p[ map.id ] = id;
			}
			if ( map.alt && alt !== undefined ) {
				p[ map.alt ] = alt;
			}
			onChange( p );
		}

		function setOne( key, v ) {
			var p = {};
			p[ key ] = v;
			onChange( p );
		}

		return el(
			'div',
			{ style: { margin: '0 0 16px' } },
			el( 'p', { style: LABEL }, label ),
			src
				? el( 'img', { src: src, alt: '', style: { display: 'block', maxWidth: '100%', maxHeight: '140px', marginBottom: '8px' } } )
				: el( 'p', { style: { margin: '0 0 8px', fontSize: '13px', color: '#646970' } }, __( 'No image chosen.', D ) ),
			el(
				be.MediaUploadCheck,
				null,
				el( be.MediaUpload, {
					allowedTypes: [ 'image' ],
					value: map.id ? value[ map.id ] : undefined,
					onSelect: function ( media ) {
						patch( media.url, media.id, map.alt && ! value[ map.alt ] ? media.alt || '' : undefined );
					},
					render: function ( open ) {
						return el( c.Button, { variant: 'secondary', onClick: open.open }, src ? __( 'Replace image', D ) : __( 'Choose image', D ) );
					},
				} )
			),
			src ? el( c.Button, { variant: 'tertiary', isDestructive: true, onClick: function () { patch( '', 0 ); } }, __( 'Remove image', D ) ) : null,
			map.decorative ? toggle( __( 'Decorative: no alt text needed', D ), decorative, function ( v ) { setOne( map.decorative, v ); } ) : null,
			map.alt && ! decorative
				? text( __( 'Alt text', D ), value[ map.alt ], function ( v ) { setOne( map.alt, v ); }, {
					help: __( 'Say what the picture shows, for someone who cannot see it.', D ),
				} )
				: null
		);
	}

	/**
	 * A list with a smallest and largest size. Each item opens to its fields;
	 * items move up and down and can be removed (never below the smallest).
	 *
	 * @param {Object} o label, items, min, max, addLabel, blank (a new item),
	 *                   title(item, index), fields(item, index, update) and
	 *                   onChange(items).
	 */
	function list( o ) {
		var items = Array.isArray( o.items ) ? o.items : [];
		var max = o.max || 99;
		var min = o.min || 0;

		function move( i, by ) {
			var next = items.slice();
			var to = i + by;
			if ( to < 0 || to >= next.length ) {
				return;
			}
			var held = next[ i ];
			next[ i ] = next[ to ];
			next[ to ] = held;
			o.onChange( next );
		}

		var sizeNote = '';
		if ( items.length === 0 ) {
			sizeNote = __( 'Nothing here yet: this part is left out of the page.', D );
		} else if ( min !== max ) {
			sizeNote = ( min > 0 ? __( 'At least', D ) + ' ' + min + '. ' : '' ) + __( 'Up to', D ) + ' ' + max + '.';
		}

		return el(
			'div',
			{ style: { margin: '0 0 16px' } },
			el( 'p', { style: LABEL }, o.label ),
			el( 'p', { style: { margin: '0 0 8px', fontSize: '12px', color: '#646970' } }, sizeNote ),
			items.map( function ( item, i ) {
				function update( patch ) {
					var next = items.slice();
					next[ i ] = typeof item === 'object' && item !== null ? Object.assign( {}, item, patch ) : patch;
					o.onChange( next );
				}
				function remove() {
					o.onChange( items.filter( function ( x, k ) { return k !== i; } ) );
				}
				return el(
					c.PanelBody,
					{ key: i, title: o.title( item, i ), initialOpen: false },
					o.fields( item, i, update ),
					el(
						'div',
						{ style: { display: 'flex', gap: '8px', marginTop: '8px' } },
						el( c.Button, { variant: 'secondary', size: 'small', disabled: i === 0, onClick: function () { move( i, -1 ); } }, __( 'Move up', D ) ),
						el( c.Button, { variant: 'secondary', size: 'small', disabled: i === items.length - 1, onClick: function () { move( i, 1 ); } }, __( 'Move down', D ) ),
						el( c.Button, { variant: 'tertiary', isDestructive: true, size: 'small', disabled: items.length <= min, onClick: remove }, __( 'Remove', D ) )
					)
				);
			} ),
			el(
				c.Button,
				{
					variant: 'secondary',
					disabled: items.length >= max,
					onClick: function () { o.onChange( items.concat( [ typeof o.blank === 'function' ? o.blank() : o.blank ] ) ); },
				},
				o.addLabel || __( 'Add', D )
			)
		);
	}

	window.brodfestEditor = {
		el: el,
		__: function ( s ) { return __( s, D ); },
		resolve: resolve,
		section: section,
		text: text,
		choice: choice,
		toggle: toggle,
		image: image,
		list: list,
		Fragment: wp.element.Fragment,
		InnerBlocks: be.InnerBlocks,
		register: function ( name, edit, save, extra ) {
			// The header and footer are drawn around every page by the plugin:
			// they are not sections of a page, so they have nothing to show or hide.
			var wrapped = name === 'brodfest/site-header' || name === 'brodfest/site-footer' ? edit : function ( props ) {
				drawingHidden = !! props.attributes.hidden;
				return el(
					wp.element.Fragment,
					null,
					el(
						be.InspectorControls,
						null,
						el(
							c.PanelBody,
							{ title: __( 'Visibility', D ), initialOpen: true },
							toggle(
								__( 'Show this section on the site', D ),
								! props.attributes.hidden,
								function ( v ) { props.setAttributes( { hidden: ! v } ); },
								__( 'Switch off to keep the section on the page without showing it to visitors.', D )
							)
						)
					),
					edit( props )
				);
			};
			wp.blocks.registerBlockType( name, Object.assign( {
				edit: wrapped,
				// Nothing is saved but the attributes: render.php draws the section.
				save: save || function () { return null; },
			}, extra || {} ) );
		},
	};
}() );
