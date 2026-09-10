/**
 * Editor UI for the theme's block-based homepage/area management blocks:
 * wm/home-section, wm/product-carousel, wm/filter-section, wm/price-filter-card.
 *
 * All four blocks are dynamic (server-rendered via render.php), so every
 * edit() below just shows InspectorControls for the attributes plus a
 * ServerSideRender preview (or, for wm/filter-section, native InnerBlocks) —
 * there is no separate front-end markup to keep in sync.
 */
( function ( wp ) {
	'use strict';

	var el = wp.element.createElement;
	var Fragment = wp.element.Fragment;
	var registerBlockType = wp.blocks.registerBlockType;
	var InspectorControls = wp.blockEditor.InspectorControls;
	var useBlockProps = wp.blockEditor.useBlockProps;
	var InnerBlocks = wp.blockEditor.InnerBlocks;
	var MediaUpload = wp.blockEditor.MediaUpload;
	var MediaUploadCheck = wp.blockEditor.MediaUploadCheck;
	var PanelColorSettings = wp.blockEditor.PanelColorSettings;
	var PanelBody = wp.components.PanelBody;
	var SelectControl = wp.components.SelectControl;
	var TextControl = wp.components.TextControl;
	var TextareaControl = wp.components.TextareaControl;
	var ToggleControl = wp.components.ToggleControl;
	var RangeControl = wp.components.RangeControl;
	var Button = wp.components.Button;
	var __ = wp.i18n.__;
	var useSelect = wp.data.useSelect;
	var ServerSideRender = wp.serverSideRender && wp.serverSideRender.default ? wp.serverSideRender.default : wp.serverSideRender;

	var data = window.wmBlockEditorData || { homeSections: {}, taxonomies: {} };

	function toOptions( map, emptyLabel ) {
		var options = [];
		if ( emptyLabel ) {
			options.push( { label: emptyLabel, value: '' } );
		}
		Object.keys( map ).forEach( function ( key ) {
			var value = map[ key ];
			options.push( { label: typeof value === 'object' ? value.label : value, value: key } );
		} );
		return options;
	}

	/* ---------------------------------------------------------------------
	 * wm/home-section
	 * ------------------------------------------------------------------ */
	registerBlockType( 'wm/home-section', {
		edit: function ( props ) {
			var attributes = props.attributes;
			var blockProps = useBlockProps();

			return el(
				Fragment,
				null,
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{ title: __( 'بخش', 'eshobe-ecommerce' ) },
						el( SelectControl, {
							label: __( 'کدام بخش آماده نمایش داده شود؟', 'eshobe-ecommerce' ),
							value: attributes.section,
							options: toOptions( data.homeSections ),
							onChange: function ( value ) {
								props.setAttributes( { section: value } );
							},
						} )
					)
				),
				el(
					'div',
					blockProps,
					el( ServerSideRender, {
						block: 'wm/home-section',
						attributes: attributes,
					} )
				)
			);
		},
		save: function () {
			return null;
		},
	} );

	/* ---------------------------------------------------------------------
	 * wm/product-carousel
	 * ------------------------------------------------------------------ */
	registerBlockType( 'wm/product-carousel', {
		edit: function ( props ) {
			var attributes = props.attributes;
			var setAttributes = props.setAttributes;
			var blockProps = useBlockProps();
			var isTaxonomy = attributes.source === 'taxonomy';

			return el(
				Fragment,
				null,
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{ title: __( 'منبع محصولات', 'eshobe-ecommerce' ) },
						el( SelectControl, {
							label: __( 'منبع', 'eshobe-ecommerce' ),
							value: attributes.source,
							options: [
								{ label: __( 'دسته/برچسب/برند دلخواه', 'eshobe-ecommerce' ), value: 'taxonomy' },
								{ label: __( 'پرفروش‌ترین‌ها (خودکار)', 'eshobe-ecommerce' ), value: 'bestsellers' },
								{ label: __( 'پیشنهادی ما (خودکار)', 'eshobe-ecommerce' ), value: 'recommended' },
							],
							onChange: function ( value ) {
								setAttributes( { source: value } );
							},
						} ),
						isTaxonomy &&
							el( SelectControl, {
								label: __( 'طبقه‌بندی', 'eshobe-ecommerce' ),
								value: attributes.taxonomy,
								options: toOptions( data.taxonomies ),
								onChange: function ( value ) {
									setAttributes( { taxonomy: value } );
								},
							} ),
						isTaxonomy &&
							el( TextControl, {
								label: __( 'اسلاگ یا شناسه ترم‌ها (با کاما جدا کنید، خالی = همه)', 'eshobe-ecommerce' ),
								value: attributes.terms,
								onChange: function ( value ) {
									setAttributes( { terms: value } );
								},
							} )
					),
					el(
						PanelBody,
						{ title: __( 'نمایش', 'eshobe-ecommerce' ) },
						el( TextControl, {
							label: __( 'عنوان', 'eshobe-ecommerce' ),
							value: attributes.title,
							onChange: function ( value ) {
								setAttributes( { title: value } );
							},
						} ),
						el( TextControl, {
							label: __( 'زیرعنوان', 'eshobe-ecommerce' ),
							value: attributes.subtitle,
							onChange: function ( value ) {
								setAttributes( { subtitle: value } );
							},
						} ),
						el( RangeControl, {
							label: __( 'تعداد محصولات', 'eshobe-ecommerce' ),
							value: attributes.count,
							min: 1,
							max: 24,
							onChange: function ( value ) {
								setAttributes( { count: value } );
							},
						} ),
						el( SelectControl, {
							label: __( 'ترتیب بر اساس', 'eshobe-ecommerce' ),
							value: attributes.orderby,
							options: [
								{ label: __( 'تاریخ', 'eshobe-ecommerce' ), value: 'date' },
								{ label: __( 'قیمت', 'eshobe-ecommerce' ), value: 'price' },
								{ label: __( 'محبوبیت (فروش)', 'eshobe-ecommerce' ), value: 'popularity' },
								{ label: __( 'تصادفی', 'eshobe-ecommerce' ), value: 'rand' },
								{ label: __( 'ترتیب دستی محصول', 'eshobe-ecommerce' ), value: 'menu_order' },
							],
							onChange: function ( value ) {
								setAttributes( { orderby: value } );
							},
						} ),
						el( SelectControl, {
							label: __( 'جهت ترتیب', 'eshobe-ecommerce' ),
							value: attributes.order,
							options: [
								{ label: __( 'نزولی', 'eshobe-ecommerce' ), value: 'DESC' },
								{ label: __( 'صعودی', 'eshobe-ecommerce' ), value: 'ASC' },
							],
							onChange: function ( value ) {
								setAttributes( { order: value } );
							},
						} )
					)
				),
				el(
					'div',
					blockProps,
					el( ServerSideRender, {
						block: 'wm/product-carousel',
						attributes: attributes,
					} )
				)
			);
		},
		save: function () {
			return null;
		},
	} );

	/* ---------------------------------------------------------------------
	 * wm/post-carousel
	 * ------------------------------------------------------------------ */
	registerBlockType( 'wm/post-carousel', {
		edit: function ( props ) {
			var attributes = props.attributes;
			var setAttributes = props.setAttributes;
			var blockProps = useBlockProps();

			return el(
				Fragment,
				null,
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{ title: __( 'تنظیمات مقالات', 'eshobe-ecommerce' ) },
						el( TextControl, {
							label: __( 'اسلاگ دسته مقالات (خالی = همه)', 'eshobe-ecommerce' ),
							value: attributes.category,
							onChange: function ( value ) {
								setAttributes( { category: value } );
							},
						} ),
						el( RangeControl, {
							label: __( 'تعداد مقالات', 'eshobe-ecommerce' ),
							value: attributes.count,
							min: 1,
							max: 24,
							onChange: function ( value ) {
								setAttributes( { count: value } );
							},
						} ),
						el( SelectControl, {
							label: __( 'ترتیب بر اساس', 'eshobe-ecommerce' ),
							value: attributes.orderby,
							options: [
								{ label: __( 'تاریخ', 'eshobe-ecommerce' ), value: 'date' },
								{ label: __( 'عنوان', 'eshobe-ecommerce' ), value: 'title' },
								{ label: __( 'تصادفی', 'eshobe-ecommerce' ), value: 'rand' },
							],
							onChange: function ( value ) {
								setAttributes( { orderby: value } );
							},
						} ),
						el( SelectControl, {
							label: __( 'جهت ترتیب', 'eshobe-ecommerce' ),
							value: attributes.order,
							options: [
								{ label: __( 'نزولی', 'eshobe-ecommerce' ), value: 'DESC' },
								{ label: __( 'صعودی', 'eshobe-ecommerce' ), value: 'ASC' },
							],
							onChange: function ( value ) {
								setAttributes( { order: value } );
							},
						} )
					),
					el(
						PanelBody,
						{ title: __( 'نمایش', 'eshobe-ecommerce' ) },
						el( TextControl, {
							label: __( 'عنوان', 'eshobe-ecommerce' ),
							value: attributes.title,
							onChange: function ( value ) {
								setAttributes( { title: value } );
							},
						} ),
						el( TextControl, {
							label: __( 'زیرعنوان', 'eshobe-ecommerce' ),
							value: attributes.subtitle,
							onChange: function ( value ) {
								setAttributes( { subtitle: value } );
							},
						} ),
						el( RangeControl, {
							label: __( 'ستون‌ها (دسکتاپ)', 'eshobe-ecommerce' ),
							value: attributes.columnsDesktop,
							min: 1,
							max: 6,
							onChange: function ( value ) {
								setAttributes( { columnsDesktop: value } );
							},
						} ),
						el( RangeControl, {
							label: __( 'ستون‌ها (تبلت)', 'eshobe-ecommerce' ),
							value: attributes.columnsTablet,
							min: 1,
							max: 4,
							onChange: function ( value ) {
								setAttributes( { columnsTablet: value } );
							},
						} ),
						el( RangeControl, {
							label: __( 'ستون‌ها (موبایل)', 'eshobe-ecommerce' ),
							value: attributes.columnsMobile,
							min: 1,
							max: 3,
							onChange: function ( value ) {
								setAttributes( { columnsMobile: value } );
							},
						} )
					)
				),
				el(
					'div',
					blockProps,
					el( ServerSideRender, {
						block: 'wm/post-carousel',
						attributes: attributes,
					} )
				)
			);
		},
		save: function () {
			return null;
		},
	} );

	/* ---------------------------------------------------------------------
	 * wm/filter-section (InnerBlocks container for wm/price-filter-card)
	 * ------------------------------------------------------------------ */
	registerBlockType( 'wm/filter-section', {
		edit: function ( props ) {
			var attributes = props.attributes;
			var setAttributes = props.setAttributes;
			var blockProps = useBlockProps( { className: 'wm-home-filters__grid' } );

			return el(
				Fragment,
				null,
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{ title: __( 'عنوان بخش', 'eshobe-ecommerce' ) },
						el( TextControl, {
							label: __( 'عنوان', 'eshobe-ecommerce' ),
							value: attributes.title,
							onChange: function ( value ) {
								setAttributes( { title: value } );
							},
						} ),
						el( TextControl, {
							label: __( 'زیرعنوان', 'eshobe-ecommerce' ),
							value: attributes.subtitle,
							onChange: function ( value ) {
								setAttributes( { subtitle: value } );
							},
						} )
					)
				),
				el(
					'div',
					blockProps,
					el( InnerBlocks, {
						allowedBlocks: [ 'wm/price-filter-card' ],
						template: [ [ 'wm/price-filter-card' ] ],
						templateInsertUpdatesSelection: false,
					} )
				)
			);
		},
		save: function () {
			return el( InnerBlocks.Content );
		},
	} );

	/* ---------------------------------------------------------------------
	 * wm/price-filter-card
	 * ------------------------------------------------------------------ */
	// Two-level picker for hierarchical taxonomies: a parent dropdown (top-level
	// terms only) plus an optional child dropdown that appears once a parent
	// with children is chosen. Picking a parent alone links to the parent
	// term itself; picking a child narrows to that child.
	function HierarchicalTermSelect( props ) {
		var taxonomy = props.taxonomy;
		var value = props.value;
		var onChange = props.onChange;

		var selectedTerm = useSelect(
			function ( select ) {
				return value ? select( 'core' ).getEntityRecord( 'taxonomy', taxonomy, value ) : null;
			},
			[ taxonomy, value ]
		);
		var parents = useSelect(
			function ( select ) {
				return select( 'core' ).getEntityRecords( 'taxonomy', taxonomy, { parent: 0, per_page: -1, hide_empty: false, orderby: 'name', order: 'asc' } );
			},
			[ taxonomy ]
		);

		var parentValue = selectedTerm ? ( selectedTerm.parent || selectedTerm.id ) : 0;

		var children = useSelect(
			function ( select ) {
				return parentValue ? select( 'core' ).getEntityRecords( 'taxonomy', taxonomy, { parent: parentValue, per_page: -1, hide_empty: false, orderby: 'name', order: 'asc' } ) : null;
			},
			[ taxonomy, parentValue ]
		);

		var parentOptions = [ { label: __( 'انتخاب کنید…', 'eshobe-ecommerce' ), value: 0 } ].concat(
			( parents || [] ).map( function ( term ) {
				return { label: term.name, value: term.id };
			} )
		);
		var childValue = selectedTerm && selectedTerm.parent ? selectedTerm.id : 0;
		var childOptions = [ { label: __( 'استفاده از دسته والد (بدون زیرمجموعه)', 'eshobe-ecommerce' ), value: 0 } ].concat(
			( children || [] ).map( function ( term ) {
				return { label: term.name, value: term.id };
			} )
		);

		return el(
			Fragment,
			null,
			el( SelectControl, {
				label: __( 'طبقه‌بندی والد', 'eshobe-ecommerce' ),
				value: parentValue,
				options: parentOptions,
				onChange: function ( newValue ) {
					onChange( parseInt( newValue, 10 ) || 0 );
				},
			} ),
			!! ( parentValue && children && children.length ) &&
				el( SelectControl, {
					label: __( 'زیرمجموعه (اختیاری)', 'eshobe-ecommerce' ),
					value: childValue,
					options: childOptions,
					onChange: function ( newValue ) {
						var id = parseInt( newValue, 10 ) || 0;
						onChange( id || parentValue );
					},
				} )
		);
	}

	function FlatTermSelect( props ) {
		var taxonomy = props.taxonomy;
		var value = props.value;
		var onChange = props.onChange;

		var terms = useSelect(
			function ( select ) {
				return select( 'core' ).getEntityRecords( 'taxonomy', taxonomy, { per_page: -1, hide_empty: false, orderby: 'name', order: 'asc' } );
			},
			[ taxonomy ]
		);

		var options = [ { label: __( 'انتخاب کنید…', 'eshobe-ecommerce' ), value: 0 } ].concat(
			( terms || [] ).map( function ( term ) {
				return { label: term.name, value: term.id };
			} )
		);

		return el( SelectControl, {
			label: __( 'ترم', 'eshobe-ecommerce' ),
			value: value,
			options: options,
			onChange: function ( newValue ) {
				onChange( parseInt( newValue, 10 ) || 0 );
			},
		} );
	}

	function TermSelect( props ) {
		return props.hierarchical ? el( HierarchicalTermSelect, props ) : el( FlatTermSelect, props );
	}

	registerBlockType( 'wm/price-filter-card', {
		edit: function ( props ) {
			var attributes = props.attributes;
			var setAttributes = props.setAttributes;
			var blockProps = useBlockProps();
			var linkMode = attributes.linkMode;
			var needsTerm = linkMode === 'taxonomy_term' || linkMode === 'taxonomy_and_price';
			var needsPrice = linkMode === 'price_range' || linkMode === 'taxonomy_and_price';
			var needsUrl = linkMode === 'manual_url';

			return el(
				Fragment,
				null,
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{ title: __( 'متن کارت', 'eshobe-ecommerce' ) },
						el( TextControl, {
							label: __( 'عنوان', 'eshobe-ecommerce' ),
							value: attributes.filterTitle,
							onChange: function ( value ) {
								setAttributes( { filterTitle: value } );
							},
						} ),
						el( TextControl, {
							label: __( 'زیرعنوان', 'eshobe-ecommerce' ),
							value: attributes.filterSubtitle,
							onChange: function ( value ) {
								setAttributes( { filterSubtitle: value } );
							},
						} ),
						el( TextControl, {
							label: __( 'متن نشان (اختیاری)', 'eshobe-ecommerce' ),
							value: attributes.badgeText,
							onChange: function ( value ) {
								setAttributes( { badgeText: value } );
							},
						} ),
						el( TextControl, {
							label: __( 'متن دکمه', 'eshobe-ecommerce' ),
							placeholder: __( 'مشاهده', 'eshobe-ecommerce' ),
							value: attributes.buttonText,
							onChange: function ( value ) {
								setAttributes( { buttonText: value } );
							},
						} ),
						el( SelectControl, {
							label: __( 'ظاهر کارت', 'eshobe-ecommerce' ),
							value: attributes.style,
							options: [
								{ label: __( 'تیره', 'eshobe-ecommerce' ), value: 'dark_card' },
								{ label: __( 'روشن', 'eshobe-ecommerce' ), value: 'light_card' },
							],
							onChange: function ( value ) {
								setAttributes( { style: value } );
							},
						} ),
						el( ToggleControl, {
							label: __( 'نمایش پوشش روی تصویر', 'eshobe-ecommerce' ),
							help: __( 'غیرفعال کنید تا لایه رنگی روی تصویر کارت کاملاً حذف شود.', 'eshobe-ecommerce' ),
							checked: attributes.coverEnabled !== false,
							onChange: function ( value ) {
								setAttributes( { coverEnabled: value } );
							},
						} )
					),
					el(
						PanelBody,
						{ title: __( 'تصویر', 'eshobe-ecommerce' ) },
						el(
							MediaUploadCheck,
							null,
							el( MediaUpload, {
								onSelect: function ( media ) {
									setAttributes( { imageId: media.id } );
								},
								allowedTypes: [ 'image' ],
								value: attributes.imageId,
								render: function ( mediaProps ) {
									return el(
										Button,
										{ variant: 'secondary', onClick: mediaProps.open },
										attributes.imageId ? __( 'تغییر تصویر', 'eshobe-ecommerce' ) : __( 'انتخاب تصویر', 'eshobe-ecommerce' )
									);
								},
							} )
						),
						attributes.imageId
							? el(
									Button,
									{ variant: 'link', isDestructive: true, onClick: function () {
										setAttributes( { imageId: 0 } );
									} },
									__( 'حذف تصویر', 'eshobe-ecommerce' )
							  )
							: null
					),
					el(
						PanelBody,
						{ title: __( 'لینک کارت', 'eshobe-ecommerce' ) },
						el( SelectControl, {
							label: __( 'نوع لینک', 'eshobe-ecommerce' ),
							value: linkMode,
							options: [
								{ label: __( 'محدوده قیمت', 'eshobe-ecommerce' ), value: 'price_range' },
								{ label: __( 'دسته/برند مشخص', 'eshobe-ecommerce' ), value: 'taxonomy_term' },
								{ label: __( 'دسته/برند + محدوده قیمت', 'eshobe-ecommerce' ), value: 'taxonomy_and_price' },
								{ label: __( 'آدرس دلخواه', 'eshobe-ecommerce' ), value: 'manual_url' },
							],
							onChange: function ( value ) {
								setAttributes( { linkMode: value } );
							},
						} ),
						needsUrl &&
							el( TextControl, {
								label: __( 'آدرس', 'eshobe-ecommerce' ),
								value: attributes.manualUrl,
								onChange: function ( value ) {
									setAttributes( { manualUrl: value } );
								},
							} ),
						needsTerm &&
							el( SelectControl, {
								label: __( 'طبقه‌بندی', 'eshobe-ecommerce' ),
								value: attributes.taxonomy,
								options: toOptions( data.taxonomies ),
								onChange: function ( value ) {
									setAttributes( { taxonomy: value, termId: 0 } );
								},
							} ),
						needsTerm &&
							el( TermSelect, {
								taxonomy: attributes.taxonomy,
								hierarchical: !! ( data.taxonomies[ attributes.taxonomy ] && data.taxonomies[ attributes.taxonomy ].hierarchical ),
								value: attributes.termId,
								onChange: function ( value ) {
									setAttributes( { termId: value } );
								},
							} ),
						needsPrice &&
							el( TextControl, {
								label: __( 'حداقل قیمت (اختیاری)', 'eshobe-ecommerce' ),
								type: 'number',
								value: attributes.minPrice || '',
								onChange: function ( value ) {
									setAttributes( { minPrice: parseInt( value, 10 ) || 0 } );
								},
							} ),
						needsPrice &&
							el( TextControl, {
								label: __( 'حداکثر قیمت (اختیاری)', 'eshobe-ecommerce' ),
								type: 'number',
								value: attributes.maxPrice || '',
								onChange: function ( value ) {
									setAttributes( { maxPrice: parseInt( value, 10 ) || 0 } );
								},
							} )
					),
					PanelColorSettings
						? el( PanelColorSettings, {
								title: __( 'رنگ اختصاصی کارت (اختیاری)', 'eshobe-ecommerce' ),
								initialOpen: false,
								colorSettings: [
									{
										value: attributes.colorValue,
										onChange: function ( value ) {
											setAttributes( { colorValue: value || '' } );
										},
										label: __( 'رنگ تاکید', 'eshobe-ecommerce' ),
									},
									{
										value: attributes.coverColor,
										onChange: function ( value ) {
											setAttributes( { coverColor: value || '' } );
										},
										label: __( 'رنگ پوشش', 'eshobe-ecommerce' ),
									},
								],
						  } )
						: null
				),
				el(
					'div',
					blockProps,
					el( ServerSideRender, {
						block: 'wm/price-filter-card',
						attributes: attributes,
					} )
				)
			);
		},
		save: function () {
			return null;
		},
	} );
} )( window.wp );
