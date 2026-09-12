/**
 * Editor UI for the theme's company-page block family (About Us / Contact Us):
 * wm/page-hero, wm/about-story, wm/stats-row(+item), wm/feature-cards(+card),
 * wm/team-grid(+member), wm/contact-cards(+card), wm/contact-form,
 * wm/map-embed, wm/faq(+item), wm/cta-banner.
 *
 * All blocks are dynamic (server-rendered via render.php), so every edit()
 * shows InspectorControls plus either a ServerSideRender preview or native
 * InnerBlocks for container blocks — the same architecture as the existing
 * homepage blocks in blocks-editor.js.
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
	var PanelBody = wp.components.PanelBody;
	var SelectControl = wp.components.SelectControl;
	var TextControl = wp.components.TextControl;
	var TextareaControl = wp.components.TextareaControl;
	var ToggleControl = wp.components.ToggleControl;
	var RangeControl = wp.components.RangeControl;
	var Button = wp.components.Button;
	var __ = wp.i18n.__;
	var ServerSideRender = wp.serverSideRender && wp.serverSideRender.default ? wp.serverSideRender.default : wp.serverSideRender;

	var data = window.wmCompanyEditorData || { icons: {} };

	function iconOptions() {
		return Object.keys( data.icons || {} ).map( function ( key ) {
			return { label: data.icons[ key ], value: key };
		} );
	}

	function ssr( blockName, attributes, blockProps ) {
		return el( 'div', blockProps, el( ServerSideRender, { block: blockName, attributes: attributes } ) );
	}

	function textControl( props, attr, label, placeholder ) {
		return el( TextControl, {
			label: label,
			placeholder: placeholder || '',
			value: props.attributes[ attr ],
			onChange: function ( value ) {
				var next = {};
				next[ attr ] = value;
				props.setAttributes( next );
			},
		} );
	}

	function textareaControl( props, attr, label, help ) {
		return el( TextareaControl, {
			label: label,
			help: help || '',
			value: props.attributes[ attr ],
			onChange: function ( value ) {
				var next = {};
				next[ attr ] = value;
				props.setAttributes( next );
			},
		} );
	}

	function imageControl( props, attr, label ) {
		return el(
			Fragment,
			null,
			el(
				MediaUploadCheck,
				null,
				el( MediaUpload, {
					onSelect: function ( media ) {
						var next = {};
						next[ attr ] = media.id;
						props.setAttributes( next );
					},
					allowedTypes: [ 'image' ],
					value: props.attributes[ attr ],
					render: function ( mediaProps ) {
						return el(
							Button,
							{ variant: 'secondary', onClick: mediaProps.open },
							props.attributes[ attr ] ? __( 'تغییر تصویر', 'eshobe-ecommerce' ) : label || __( 'انتخاب تصویر', 'eshobe-ecommerce' )
						);
					},
				} )
			),
			props.attributes[ attr ]
				? el(
						Button,
						{
							variant: 'link',
							isDestructive: true,
							onClick: function () {
								var next = {};
								next[ attr ] = 0;
								props.setAttributes( next );
							},
						},
						__( 'حذف تصویر', 'eshobe-ecommerce' )
				  )
				: null
		);
	}

	function headerPanel( props ) {
		return el(
			PanelBody,
			{ title: __( 'عنوان بخش', 'eshobe-ecommerce' ) },
			textControl( props, 'title', __( 'عنوان', 'eshobe-ecommerce' ) ),
			textControl( props, 'subtitle', __( 'زیرعنوان', 'eshobe-ecommerce' ) )
		);
	}

	// Container block with InnerBlocks: shared factory.
	function registerContainer( name, options ) {
		registerBlockType( name, {
			edit: function ( props ) {
				var blockProps = useBlockProps( { className: options.editorClassName || '' } );
				return el(
					Fragment,
					null,
					el( InspectorControls, null, headerPanel( props ), options.extraPanels ? options.extraPanels( props ) : null ),
					el(
						'div',
						blockProps,
						props.attributes.title
							? el( 'h2', { className: 'wm-company-editor-heading' }, props.attributes.title )
							: null,
						el( InnerBlocks, {
							allowedBlocks: [ options.child ],
							template: options.template,
							templateInsertUpdatesSelection: false,
							orientation: options.orientation || 'horizontal',
						} )
					)
				);
			},
			save: function () {
				return el( InnerBlocks.Content );
			},
		} );
	}

	/* ---------------------------------------------------------------------
	 * wm/page-hero
	 * ------------------------------------------------------------------ */
	registerBlockType( 'wm/page-hero', {
		edit: function ( props ) {
			var blockProps = useBlockProps();
			return el(
				Fragment,
				null,
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{ title: __( 'محتوا', 'eshobe-ecommerce' ) },
						textControl( props, 'eyebrow', __( 'نشان بالای عنوان (اختیاری)', 'eshobe-ecommerce' ), __( 'مثلاً: قصه ما', 'eshobe-ecommerce' ) ),
						textControl( props, 'title', __( 'عنوان (خالی = عنوان صفحه)', 'eshobe-ecommerce' ) ),
						textareaControl( props, 'subtitle', __( 'توضیح کوتاه', 'eshobe-ecommerce' ) ),
						el( ToggleControl, {
							label: __( 'نمایش مسیر (breadcrumb)', 'eshobe-ecommerce' ),
							checked: props.attributes.showBreadcrumb !== false,
							onChange: function ( value ) {
								props.setAttributes( { showBreadcrumb: value } );
							},
						} )
					),
					el( PanelBody, { title: __( 'تصویر (اختیاری)', 'eshobe-ecommerce' ), initialOpen: false }, imageControl( props, 'imageId' ) )
				),
				ssr( 'wm/page-hero', props.attributes, blockProps )
			);
		},
		save: function () {
			return null;
		},
	} );

	/* ---------------------------------------------------------------------
	 * wm/about-story
	 * ------------------------------------------------------------------ */
	registerBlockType( 'wm/about-story', {
		edit: function ( props ) {
			var blockProps = useBlockProps();
			return el(
				Fragment,
				null,
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{ title: __( 'محتوا', 'eshobe-ecommerce' ) },
						textControl( props, 'title', __( 'عنوان', 'eshobe-ecommerce' ) ),
						textControl( props, 'subtitle', __( 'زیرعنوان', 'eshobe-ecommerce' ) ),
						textareaControl( props, 'content', __( 'متن', 'eshobe-ecommerce' ), __( 'هر پاراگراف را با یک خط خالی جدا کنید.', 'eshobe-ecommerce' ) )
					),
					el(
						PanelBody,
						{ title: __( 'تصویر', 'eshobe-ecommerce' ), initialOpen: false },
						imageControl( props, 'imageId' ),
						el( SelectControl, {
							label: __( 'جای تصویر', 'eshobe-ecommerce' ),
							value: props.attributes.imageSide,
							options: [
								{ label: __( 'ابتدای ردیف (راست در RTL)', 'eshobe-ecommerce' ), value: 'start' },
								{ label: __( 'انتهای ردیف (چپ در RTL)', 'eshobe-ecommerce' ), value: 'end' },
							],
							onChange: function ( value ) {
								props.setAttributes( { imageSide: value } );
							},
						} ),
						textControl( props, 'badgeText', __( 'متن نشان روی تصویر (اختیاری)', 'eshobe-ecommerce' ), __( 'مثلاً: از ۱۳۹۵ کنار شما', 'eshobe-ecommerce' ) )
					)
				),
				ssr( 'wm/about-story', props.attributes, blockProps )
			);
		},
		save: function () {
			return null;
		},
	} );

	/* ---------------------------------------------------------------------
	 * Containers: stats-row, feature-cards, team-grid, contact-cards, faq
	 * ------------------------------------------------------------------ */
	registerContainer( 'wm/stats-row', {
		child: 'wm/stat-item',
		template: [ [ 'wm/stat-item' ], [ 'wm/stat-item' ], [ 'wm/stat-item' ] ],
		editorClassName: 'wm-company-stats__grid',
	} );

	registerContainer( 'wm/feature-cards', {
		child: 'wm/feature-card',
		template: [ [ 'wm/feature-card' ], [ 'wm/feature-card' ], [ 'wm/feature-card' ] ],
		editorClassName: 'wm-company-features__grid',
		extraPanels: function ( props ) {
			return el(
				PanelBody,
				{ title: __( 'نمایش', 'eshobe-ecommerce' ) },
				el( RangeControl, {
					label: __( 'ستون‌ها (دسکتاپ)', 'eshobe-ecommerce' ),
					value: props.attributes.columns,
					min: 2,
					max: 4,
					onChange: function ( value ) {
						props.setAttributes( { columns: value } );
					},
				} )
			);
		},
	} );

	registerContainer( 'wm/team-grid', {
		child: 'wm/team-member',
		template: [ [ 'wm/team-member' ], [ 'wm/team-member' ], [ 'wm/team-member' ] ],
		editorClassName: 'wm-company-team__grid',
	} );

	registerContainer( 'wm/contact-cards', {
		child: 'wm/contact-card',
		template: [ [ 'wm/contact-card' ], [ 'wm/contact-card' ], [ 'wm/contact-card' ] ],
		editorClassName: 'wm-company-contact-cards__grid',
	} );

	registerContainer( 'wm/faq', {
		child: 'wm/faq-item',
		template: [ [ 'wm/faq-item' ], [ 'wm/faq-item' ] ],
		editorClassName: 'wm-company-faq__list',
		orientation: 'vertical',
	} );

	/* ---------------------------------------------------------------------
	 * wm/stat-item
	 * ------------------------------------------------------------------ */
	registerBlockType( 'wm/stat-item', {
		edit: function ( props ) {
			var blockProps = useBlockProps();
			return el(
				Fragment,
				null,
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{ title: __( 'آمار', 'eshobe-ecommerce' ) },
						el( TextControl, {
							label: __( 'عدد', 'eshobe-ecommerce' ),
							type: 'number',
							value: props.attributes.number || '',
							onChange: function ( value ) {
								props.setAttributes( { number: parseFloat( value ) || 0 } );
							},
						} ),
						textControl( props, 'prefix', __( 'پیشوند (اختیاری)', 'eshobe-ecommerce' ) ),
						textControl( props, 'suffix', __( 'پسوند', 'eshobe-ecommerce' ), '+' ),
						textControl( props, 'label', __( 'برچسب', 'eshobe-ecommerce' ), __( 'مثلاً: مشتری خوشحال', 'eshobe-ecommerce' ) )
					)
				),
				ssr( 'wm/stat-item', props.attributes, blockProps )
			);
		},
		save: function () {
			return null;
		},
	} );

	/* ---------------------------------------------------------------------
	 * wm/feature-card
	 * ------------------------------------------------------------------ */
	registerBlockType( 'wm/feature-card', {
		edit: function ( props ) {
			var blockProps = useBlockProps();
			return el(
				Fragment,
				null,
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{ title: __( 'کارت', 'eshobe-ecommerce' ) },
						el( SelectControl, {
							label: __( 'آیکون', 'eshobe-ecommerce' ),
							value: props.attributes.icon,
							options: iconOptions(),
							onChange: function ( value ) {
								props.setAttributes( { icon: value } );
							},
						} ),
						textControl( props, 'title', __( 'عنوان', 'eshobe-ecommerce' ) ),
						textareaControl( props, 'text', __( 'توضیح', 'eshobe-ecommerce' ) )
					)
				),
				ssr( 'wm/feature-card', props.attributes, blockProps )
			);
		},
		save: function () {
			return null;
		},
	} );

	/* ---------------------------------------------------------------------
	 * wm/team-member
	 * ------------------------------------------------------------------ */
	registerBlockType( 'wm/team-member', {
		edit: function ( props ) {
			var blockProps = useBlockProps();
			return el(
				Fragment,
				null,
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{ title: __( 'عضو تیم', 'eshobe-ecommerce' ) },
						imageControl( props, 'imageId', __( 'انتخاب تصویر', 'eshobe-ecommerce' ) ),
						textControl( props, 'name', __( 'نام', 'eshobe-ecommerce' ) ),
						textControl( props, 'role', __( 'سمت', 'eshobe-ecommerce' ) ),
						textareaControl( props, 'bio', __( 'توضیح کوتاه (اختیاری)', 'eshobe-ecommerce' ) )
					),
					el(
						PanelBody,
						{ title: __( 'شبکه‌های اجتماعی (اختیاری)', 'eshobe-ecommerce' ), initialOpen: false },
						textControl( props, 'instagram', __( 'اینستاگرام (آدرس کامل)', 'eshobe-ecommerce' ) ),
						textControl( props, 'telegram', __( 'تلگرام (آدرس کامل)', 'eshobe-ecommerce' ) ),
						textControl( props, 'linkedin', __( 'لینکدین (آدرس کامل)', 'eshobe-ecommerce' ) )
					)
				),
				ssr( 'wm/team-member', props.attributes, blockProps )
			);
		},
		save: function () {
			return null;
		},
	} );

	/* ---------------------------------------------------------------------
	 * wm/contact-card
	 * ------------------------------------------------------------------ */
	registerBlockType( 'wm/contact-card', {
		edit: function ( props ) {
			var blockProps = useBlockProps();
			return el(
				Fragment,
				null,
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{ title: __( 'کارت تماس', 'eshobe-ecommerce' ) },
						el( SelectControl, {
							label: __( 'آیکون', 'eshobe-ecommerce' ),
							value: props.attributes.icon,
							options: iconOptions(),
							onChange: function ( value ) {
								props.setAttributes( { icon: value } );
							},
						} ),
						textControl( props, 'title', __( 'عنوان', 'eshobe-ecommerce' ), __( 'مثلاً: تلفن پشتیبانی', 'eshobe-ecommerce' ) ),
						textareaControl( props, 'lines', __( 'خطوط متن (هر خط یک مورد)', 'eshobe-ecommerce' ), __( 'مثلاً شماره تلفن یا ساعت کاری؛ هر مورد در یک خط.', 'eshobe-ecommerce' ) )
					),
					el(
						PanelBody,
						{ title: __( 'لینک (اختیاری)', 'eshobe-ecommerce' ), initialOpen: false },
						textControl( props, 'linkUrl', __( 'آدرس لینک', 'eshobe-ecommerce' ), 'tel:021xxxxxxxx / mailto:info@…' ),
						textControl( props, 'linkText', __( 'متن لینک', 'eshobe-ecommerce' ), __( 'مثلاً: تماس بگیرید', 'eshobe-ecommerce' ) )
					)
				),
				ssr( 'wm/contact-card', props.attributes, blockProps )
			);
		},
		save: function () {
			return null;
		},
	} );

	/* ---------------------------------------------------------------------
	 * wm/contact-form
	 * ------------------------------------------------------------------ */
	registerBlockType( 'wm/contact-form', {
		edit: function ( props ) {
			var blockProps = useBlockProps();
			return el(
				Fragment,
				null,
				el(
					InspectorControls,
					null,
					headerPanel( props ),
					el(
						PanelBody,
						{ title: __( 'فیلدها و پیام‌ها', 'eshobe-ecommerce' ) },
						el( ToggleControl, {
							label: __( 'فیلد شماره تماس', 'eshobe-ecommerce' ),
							checked: props.attributes.showPhone !== false,
							onChange: function ( value ) {
								props.setAttributes( { showPhone: value } );
							},
						} ),
						el( ToggleControl, {
							label: __( 'فیلد موضوع', 'eshobe-ecommerce' ),
							checked: props.attributes.showSubject !== false,
							onChange: function ( value ) {
								props.setAttributes( { showSubject: value } );
							},
						} ),
						textControl( props, 'buttonText', __( 'متن دکمه', 'eshobe-ecommerce' ), __( 'ارسال پیام', 'eshobe-ecommerce' ) ),
						textareaControl( props, 'successMessage', __( 'پیام موفقیت', 'eshobe-ecommerce' ) )
					)
				),
				ssr( 'wm/contact-form', props.attributes, blockProps )
			);
		},
		save: function () {
			return null;
		},
	} );

	/* ---------------------------------------------------------------------
	 * wm/map-embed
	 * ------------------------------------------------------------------ */
	registerBlockType( 'wm/map-embed', {
		edit: function ( props ) {
			var blockProps = useBlockProps();
			return el(
				Fragment,
				null,
				el(
					InspectorControls,
					null,
					headerPanel( props ),
					el(
						PanelBody,
						{ title: __( 'نقشه', 'eshobe-ecommerce' ) },
						el( TextareaControl, {
							label: __( 'آدرس iframe نقشه (https)', 'eshobe-ecommerce' ),
							help: __( 'از گوگل‌مپ/نشان/بلد گزینه «جاسازی/embed» را بردارید و فقط آدرس داخل src را اینجا بگذارید.', 'eshobe-ecommerce' ),
							value: props.attributes.embedUrl,
							onChange: function ( value ) {
								props.setAttributes( { embedUrl: value.trim() } );
							},
						} ),
						el( RangeControl, {
							label: __( 'ارتفاع (پیکسل)', 'eshobe-ecommerce' ),
							value: props.attributes.height,
							min: 220,
							max: 640,
							step: 10,
							onChange: function ( value ) {
								props.setAttributes( { height: value } );
							},
						} ),
						textareaControl( props, 'addressText', __( 'نشانی متنی زیر نقشه (اختیاری)', 'eshobe-ecommerce' ) )
					)
				),
				ssr( 'wm/map-embed', props.attributes, blockProps )
			);
		},
		save: function () {
			return null;
		},
	} );

	/* ---------------------------------------------------------------------
	 * wm/faq-item
	 * ------------------------------------------------------------------ */
	registerBlockType( 'wm/faq-item', {
		edit: function ( props ) {
			var blockProps = useBlockProps();
			return el(
				Fragment,
				null,
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{ title: __( 'پرسش و پاسخ', 'eshobe-ecommerce' ) },
						textControl( props, 'question', __( 'پرسش', 'eshobe-ecommerce' ) ),
						textareaControl( props, 'answer', __( 'پاسخ', 'eshobe-ecommerce' ) ),
						el( ToggleControl, {
							label: __( 'به‌صورت پیش‌فرض باز باشد', 'eshobe-ecommerce' ),
							checked: !! props.attributes.open,
							onChange: function ( value ) {
								props.setAttributes( { open: value } );
							},
						} )
					)
				),
				ssr( 'wm/faq-item', props.attributes, blockProps )
			);
		},
		save: function () {
			return null;
		},
	} );

	/* ---------------------------------------------------------------------
	 * wm/cta-banner
	 * ------------------------------------------------------------------ */
	registerBlockType( 'wm/cta-banner', {
		edit: function ( props ) {
			var blockProps = useBlockProps();
			return el(
				Fragment,
				null,
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{ title: __( 'محتوا', 'eshobe-ecommerce' ) },
						textControl( props, 'title', __( 'عنوان', 'eshobe-ecommerce' ) ),
						textareaControl( props, 'text', __( 'توضیح', 'eshobe-ecommerce' ) )
					),
					el(
						PanelBody,
						{ title: __( 'دکمه‌ها', 'eshobe-ecommerce' ) },
						textControl( props, 'primaryText', __( 'متن دکمه اصلی', 'eshobe-ecommerce' ), __( 'مشاهده فروشگاه', 'eshobe-ecommerce' ) ),
						textControl( props, 'primaryUrl', __( 'لینک دکمه اصلی (خالی = فروشگاه)', 'eshobe-ecommerce' ) ),
						textControl( props, 'secondaryText', __( 'متن دکمه دوم (اختیاری)', 'eshobe-ecommerce' ) ),
						textControl( props, 'secondaryUrl', __( 'لینک دکمه دوم', 'eshobe-ecommerce' ) )
					)
				),
				ssr( 'wm/cta-banner', props.attributes, blockProps )
			);
		},
		save: function () {
			return null;
		},
	} );
} )( window.wp );
