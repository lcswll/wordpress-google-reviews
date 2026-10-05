/**
 * Wille Reviews – block editor UI for "Google Reviews" (wille-reviews/reviews).
 * Attributes are registered in PHP (class-willerev-block.php); the preview is rendered server-side.
 */
(function (wp, config) {
	'use strict';

	if (!wp || !wp.blocks || !config) {
		return;
	}

	var el = wp.element.createElement;
	var Fragment = wp.element.Fragment;
	var be = wp.blockEditor;
	var c = wp.components;
	var ServerSideRender = wp.serverSideRender;
	var t = config.i18n;
	var d = config.defaults;

	/** Tri-state toggle: '' follows the saved default, 'yes'/'no' override it. */
	function toggle(props, key, label) {
		var value = props.attributes[key];
		var checked = '' === value ? d[key] : 'yes' === value;
		return el(c.ToggleControl, {
			label: label,
			checked: checked,
			__nextHasNoMarginBottom: true,
			onChange: function (next) {
				var update = {};
				update[key] = next ? 'yes' : 'no';
				props.setAttributes(update);
			},
		});
	}

	function edit(props) {
		var a = props.attributes;
		var set = props.setAttributes;
		var layout = a.layout || d.layout;
		var cards = ['grid', 'carousel', 'list', 'masonry'].indexOf(layout) !== -1;

		var design = el(
			c.PanelBody,
			{ title: t.design, initialOpen: true },
			el(c.SelectControl, {
				label: t.layout,
				value: layout,
				options: config.layouts,
				__nextHasNoMarginBottom: true,
				__next40pxDefaultSize: true,
				onChange: function (value) {
					set({ layout: value });
				},
			}),
			el(c.SelectControl, {
				label: t.style,
				value: a.style || d.style,
				options: config.styles,
				__nextHasNoMarginBottom: true,
				__next40pxDefaultSize: true,
				onChange: function (value) {
					set({ style: value });
				},
			}),
			el(c.BaseControl, { label: t.accent, id: 'willerev-accent', __nextHasNoMarginBottom: true },
				el(c.ColorPalette, {
					value: a.accent || d.accent,
					clearable: true,
					onChange: function (value) {
						set({ accent: value || '' });
					},
				})
			),
			cards && 'list' !== layout
				? el(c.RangeControl, {
					label: t.columns,
					min: 1,
					max: 4,
					value: a.columns || d.columns,
					__nextHasNoMarginBottom: true,
					__next40pxDefaultSize: true,
					onChange: function (value) {
						set({ columns: value || 0 });
					},
				})
				: null,
			!cards
				? el(c.SelectControl, {
					label: t.alignment,
					value: a.alignment || 'left',
					options: [
						{ value: 'left', label: t.left },
						{ value: 'center', label: t.center },
						{ value: 'right', label: t.right },
					],
					__nextHasNoMarginBottom: true,
					__next40pxDefaultSize: true,
					onChange: function (value) {
						set({ alignment: value });
					},
				})
				: null
		);

		var content = cards
			? el(
				c.PanelBody,
				{ title: t.content, initialOpen: true },
				el(c.RangeControl, {
					label: t.limit,
					min: 1,
					max: 10,
					value: a.limit || d.limit,
					__nextHasNoMarginBottom: true,
					__next40pxDefaultSize: true,
					onChange: function (value) {
						set({ limit: value || 0 });
					},
				}),
				el(c.SelectControl, {
					label: t.minRating,
					value: String(a.minRating),
					options: [
						{ value: '-1', label: '—' },
						{ value: '0', label: t.all },
						{ value: '3', label: '★★★+' },
						{ value: '4', label: '★★★★+' },
						{ value: '5', label: '★★★★★' },
					],
					__nextHasNoMarginBottom: true,
					__next40pxDefaultSize: true,
					onChange: function (value) {
						set({ minRating: parseInt(value, 10) });
					},
				}),
				el(c.SelectControl, {
					label: t.sort,
					value: a.sort || 'newest',
					options: [
						{ value: 'newest', label: t.newest },
						{ value: 'rating', label: t.best },
					],
					__nextHasNoMarginBottom: true,
					__next40pxDefaultSize: true,
					onChange: function (value) {
						set({ sort: value });
					},
				}),
				toggle(props, 'header', t.header),
				toggle(props, 'avatars', t.avatars),
				toggle(props, 'cta', t.cta),
				el('p', { className: 'components-base-control__help' }, t.defaultHint)
			)
			: el(c.PanelBody, { title: t.content, initialOpen: false }, toggle(props, 'avatars', t.avatars));

		return el(
			Fragment,
			null,
			el(be.InspectorControls, null, design, content),
			el(
				'div',
				be.useBlockProps(),
				el(c.Disabled, null, el(ServerSideRender, { block: config.name, attributes: a }))
			)
		);
	}

	wp.blocks.registerBlockType(config.name, {
		apiVersion: 3,
		title: t.title,
		description: t.description,
		icon: 'star-filled',
		category: 'widgets',
		edit: edit,
		save: function () {
			return null;
		},
	});
})(window.wp, window.willerevBlock);
