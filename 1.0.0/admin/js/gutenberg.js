(function (wp) {
	'use strict';

	if (!wp || !wp.editPost) return;

	var config = window.leimglocalGutenberg || {};
	var ajaxUrl = config.ajax_url || '';
	var nonce = config.nonce || '';
	var pasteNonce = config.paste_nonce || '';
	var postId = config.post_id || 0;
	var autoPaste = config.auto_paste !== false;
	var i18n = config.i18n || {};

	var PluginSidebar = wp.editPost && wp.editPost.PluginSidebar;
	var PluginSidebarMoreMenuItem = wp.editPost && wp.editPost.PluginSidebarMoreMenuItem;
	var registerPlugin = wp.plugins && wp.plugins.registerPlugin;
	var createElement = wp.element && wp.element.createElement;
	var Fragment = wp.element && wp.element.Fragment;
	var useState = wp.element && wp.element.useState;
	var useCallback = wp.element && wp.element.useCallback;
	var Button = wp.components && wp.components.Button;
	var PanelBody = wp.components && wp.components.PanelBody;
	var useSelect = wp.data && wp.data.useSelect;
	var useDispatch = wp.data && wp.data.useDispatch;

	if (!registerPlugin || !createElement) return;

	function LocalizeButton() {
		var content = useSelect(function (select) {
			var s = select('core/editor');
			return (s && s.getEditedPostContent) ? s.getEditedPostContent() : '';
		}, []);
		var editPost = useDispatch('core/editor');
		editPost = editPost && editPost.editPost ? editPost.editPost : null;
		var _useState = useState(false);
		var busy = _useState[0];
		var setBusy = _useState[1];
		var _useState2 = useState('');
		var message = _useState2[0];
		var setMessage = _useState2[1];

		var runLocalize = useCallback(function () {
			if (busy || !content) return;
			setBusy(true);
			setMessage(i18n.processing || '正在处理…');

			var formData = new FormData();
			formData.append('action', 'leimglocal_localize');
			formData.append('nonce', nonce);
			formData.append('content', content);
			formData.append('post_id', postId);

			fetch(ajaxUrl, { method: 'POST', body: formData, credentials: 'same-origin' })
				.then(function (res) { return res.json(); })
				.then(function (data) {
					if (data.success && data.data && data.data.content !== undefined) {
						if (editPost) editPost({ content: data.data.content });
						setMessage(data.data.message || i18n.done || '完成');
					} else {
						setMessage((data.data && data.data.message) || i18n.error || '操作失败');
					}
				})
				.catch(function () { setMessage(i18n.error || '操作失败'); })
				.finally(function () { setBusy(false); });
		}, [content, busy, nonce, postId, editPost]);

		return createElement(Fragment, null,
			createElement(Button, {
				isPrimary: true,
				isBusy: busy,
				onClick: runLocalize,
				disabled: busy
			}, i18n.button || '本地化图片'),
			message ? createElement('p', { style: { marginTop: '8px', marginBottom: 0 } }, message) : null
		);
	}

	function LeimgLocalSidebar() {
		return createElement(
			PluginSidebar || 'div',
			PluginSidebar ? { name: 'leimglocal-sidebar', title: '图片本地化' } : {},
			createElement(PanelBody || 'div', { title: '图片本地化', initialOpen: true },
				createElement(LocalizeButton, null)
			)
		);
	}

	var sidebarIcon = createElement('span', { className: 'dashicons dashicons-download', style: { marginRight: '4px' } });
	var sidebarLabel = '图片本地化';

	registerPlugin('leimglocal-sidebar', {
		render: function () {
			if (PluginSidebar && PluginSidebarMoreMenuItem) {
				return createElement(Fragment, null,
					createElement(PluginSidebarMoreMenuItem, { target: 'leimglocal-sidebar' }, sidebarLabel),
					createElement(LeimgLocalSidebar, null)
				);
			}
			// Fallback: inject button into document (no SlotFill in older WP)
			var runFallback = function () {
				var toolbar = document.querySelector('.edit-post-header__settings');
				if (toolbar && !document.getElementById('leimglocal-gutenberg-btn')) {
					var btn = document.createElement('button');
					btn.id = 'leimglocal-gutenberg-btn';
					btn.type = 'button';
					btn.className = 'components-button is-secondary';
					btn.innerHTML = '<span class="dashicons dashicons-download" style="vertical-align:middle;margin-right:4px;"></span>' + (i18n.button || '本地化图片');
					btn.addEventListener('click', function () {
						var select = wp.data.select('core/editor');
						var dispatch = wp.data.dispatch('core/editor');
						if (!select || !dispatch) return;
						var content = select.getEditedPostContent();
						if (!content) return;
						btn.disabled = true;
						var formData = new FormData();
						formData.append('action', 'leimglocal_localize');
						formData.append('nonce', nonce);
						formData.append('content', content);
						formData.append('post_id', postId);
						fetch(ajaxUrl, { method: 'POST', body: formData, credentials: 'same-origin' })
							.then(function (r) { return r.json(); })
							.then(function (data) {
								if (data.success && data.data && data.data.content !== undefined) {
									dispatch.editPost({ content: data.data.content });
								}
							})
							.finally(function () { btn.disabled = false; });
					});
					toolbar.insertBefore(btn, toolbar.firstChild);
				}
			};
			if (typeof wp.domReady === 'function') {
				wp.domReady(runFallback);
			} else {
				setTimeout(runFallback, 500);
			}
			return null;
		}
	});

	// Paste: upload image and insert block
	if (autoPaste) {
		wp.domReady(function () {
			document.addEventListener('paste', function (e) {
				if (!e.clipboardData || !e.clipboardData.items) return;
				var items = e.clipboardData.items;
				for (var i = 0; i < items.length; i++) {
					if (items[i].type.indexOf('image/') !== 0) continue;
					e.preventDefault();
					var file = items[i].getAsFile();
					if (!file) continue;
					var reader = new FileReader();
					reader.onload = function (ev) {
						var base64 = ev.target.result;
						var formData = new FormData();
						formData.append('action', 'leimglocal_paste_upload');
						formData.append('nonce', pasteNonce);
						formData.append('data', base64);
						formData.append('post_id', postId);
						fetch(ajaxUrl, { method: 'POST', body: formData, credentials: 'same-origin' })
							.then(function (r) { return r.json(); })
							.then(function (data) {
								if (data.success && data.data && data.data.url) {
									var dispatch = wp.data.dispatch('core/block-editor');
									var block = wp.blocks && wp.blocks.createBlock ? wp.blocks.createBlock('core/image', { url: data.data.url }) : null;
									if (dispatch && dispatch.insertBlocks && block) {
										dispatch.insertBlocks(block);
									}
								}
							});
					};
					reader.readAsDataURL(file);
					return;
				}
			}, true);
		});
	}
})(window.wp);
