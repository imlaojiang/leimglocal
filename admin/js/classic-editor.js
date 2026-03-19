(function () {
	'use strict';

	var config = window.leimglocalClassic || {};
	var ajaxUrl = config.ajax_url || '';
	var nonce = config.nonce || '';
	var pasteNonce = config.paste_nonce || '';
	var postId = config.post_id || 0;
	var autoPaste = config.auto_paste !== false;
	var i18n = config.i18n || {};

	function getEditorContent() {
		if (typeof wp !== 'undefined' && wp.editor && wp.editor.getDefaultEditor) {
			var ed = wp.editor.getDefaultEditor();
			if (ed && ed.getContent) {
				return ed.getContent();
			}
		}
		if (typeof tinymce !== 'undefined') {
			var editor = tinymce.get('content');
			if (editor) {
				return editor.getContent();
			}
		}
		var textarea = document.getElementById('content');
		return textarea ? textarea.value : '';
	}

	function setEditorContent(html) {
		if (typeof wp !== 'undefined' && wp.editor && wp.editor.getDefaultEditor) {
			var ed = wp.editor.getDefaultEditor();
			if (ed && ed.setContent) {
				ed.setContent(html);
				return;
			}
		}
		if (typeof tinymce !== 'undefined') {
			var editor = tinymce.get('content');
			if (editor) {
				editor.setContent(html);
				return;
			}
		}
		var textarea = document.getElementById('content');
		if (textarea) {
			textarea.value = html;
		}
	}

	function setStatus(msg, isError) {
		var el = document.querySelector('.leimglocal-status');
		if (!el) return;
		el.textContent = msg || '';
		el.style.color = isError ? '#b32d2e' : '';
	}

	function localizeImages() {
		var btn = document.getElementById('leimglocal-localize-btn');
		if (btn) btn.disabled = true;
		setStatus(i18n.processing || '正在处理…');

		var content = getEditorContent();
		var formData = new FormData();
		formData.append('action', 'leimglocal_localize');
		formData.append('nonce', nonce);
		formData.append('content', content);
		formData.append('post_id', postId);

		fetch(ajaxUrl, {
			method: 'POST',
			body: formData,
			credentials: 'same-origin'
		})
			.then(function (res) { return res.json(); })
			.then(function (data) {
				if (data.success && data.data && data.data.content !== undefined) {
					setEditorContent(data.data.content);
					setStatus(data.data.message || i18n.done || '完成');
				} else {
					setStatus((data.data && data.data.message) || i18n.error || '操作失败', true);
				}
			})
			.catch(function () {
				setStatus(i18n.error || '操作失败', true);
			})
			.finally(function () {
				if (btn) btn.disabled = false;
			});
	}

	function uploadPastedImage(base64Data, callback) {
		var formData = new FormData();
		formData.append('action', 'leimglocal_paste_upload');
		formData.append('nonce', pasteNonce);
		formData.append('data', base64Data);
		formData.append('post_id', postId);

		fetch(ajaxUrl, {
			method: 'POST',
			body: formData,
			credentials: 'same-origin'
		})
			.then(function (res) { return res.json(); })
			.then(function (data) {
				if (data.success && data.data && data.data.url) {
					callback(null, data.data.url);
				} else {
					callback(new Error((data.data && data.data.message) || 'Upload failed'));
				}
			})
			.catch(function (err) { callback(err); });
	}

	function insertImageIntoEditor(url) {
		var html = '<img src="' + url.replace(/"/g, '&quot;') + '" alt="" />';
		if (typeof tinymce !== 'undefined') {
			var editor = tinymce.get('content');
			if (editor) {
				editor.execCommand('mceInsertContent', false, html);
				return;
			}
		}
		var content = getEditorContent();
		setEditorContent(content + html);
	}

	// Paste on document (for HTML / textarea mode)
	function onDocumentPaste(e) {
		if (!autoPaste || !e.clipboardData || !e.clipboardData.items) return;
		var items = e.clipboardData.items;
		for (var i = 0; i < items.length; i++) {
			if (items[i].type.indexOf('image/') !== 0) continue;
			e.preventDefault();
			var file = items[i].getAsFile();
			if (!file) continue;
			var reader = new FileReader();
			reader.onload = function (ev) {
				var data = ev.target.result;
				uploadPastedImage(data, function (err, url) {
					if (err) return;
					insertImageIntoEditor(url);
				});
			};
			reader.readAsDataURL(file);
			return;
		}
	}

	// Paste inside TinyMCE iframe (visual mode – QQ/screenshot paste happens here)
	function attachTinyMCEPaste(editor) {
		if (!editor || !editor.on) return;
		editor.on('init', function () {
			var body = editor.getBody();
			if (!body) return;
			body.addEventListener('paste', function (e) {
				if (!autoPaste || !e.clipboardData || !e.clipboardData.items) return;
				var items = e.clipboardData.items;
				for (var i = 0; i < items.length; i++) {
					if (items[i].type.indexOf('image/') !== 0) continue;
					e.preventDefault();
					e.stopPropagation();
					var file = items[i].getAsFile();
					if (!file) continue;
					var reader = new FileReader();
					reader.onload = function (ev) {
						var data = ev.target.result;
						uploadPastedImage(data, function (err, url) {
							if (err) return;
							var imgHtml = '<img src="' + url.replace(/"/g, '&quot;') + '" alt="" />';
							editor.execCommand('mceInsertContent', false, imgHtml);
						});
					};
					reader.readAsDataURL(file);
					return;
				}
			});
		});
	}

	// Button
	var btn = document.getElementById('leimglocal-localize-btn');
	if (btn) {
		btn.addEventListener('click', localizeImages);
	}

	// Document paste (textarea / HTML mode)
	document.addEventListener('paste', onDocumentPaste, true);

	// TinyMCE: attach paste inside editor iframe so QQ/screenshot paste works
	if (typeof tinymce !== 'undefined') {
		tinymce.on('AddEditor', function (e) {
			if (e && e.editor) attachTinyMCEPaste(e.editor);
		});
		// Editor "content" may already exist (e.g. quick load)
		setTimeout(function () {
			var existing = tinymce.get('content');
			if (existing) attachTinyMCEPaste(existing);
		}, 500);
	}
})();
