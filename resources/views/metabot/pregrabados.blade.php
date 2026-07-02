<x-app-layout>
    <div class="py-6">
        <div class="sm:px-2 lg:px-4 max-w-5xl mx-auto">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-4">
                <div class="mb-3">
                    <h2 class="text-lg font-semibold text-gray-800">Pregrabados</h2>
                    <p class="text-xs text-gray-400">
                        Navega el catálogo y toca un botón para copiar su contenido al portapapeles;
                        luego pégalo donde quieras (WhatsApp, Meta, notas…). Cada foto se copia por separado.
                    </p>
                </div>

                @if(empty($quickMenu))
                    <p class="text-gray-400 py-10 text-center">No hay productos con etiquetas para mostrar.</p>
                @else
                    <div id="qr-cats" class="flex flex-wrap" style="gap:4px;border-bottom:1px solid #e5e7eb;"></div>
                    <div id="qr-buttons"></div>
                @endif
            </div>
        </div>
    </div>

    @if(!empty($quickMenu))
    <script>
    (function () {
        var MENU      = @json($quickMenu);
        var qrCats    = document.getElementById('qr-cats');
        var qrButtons = document.getElementById('qr-buttons');

        // --- Clipboard helpers ---------------------------------------------------
        // Brief in-place feedback on the button that was tapped.
        function flash(btn, msg, isError) {
            if (!btn) return;
            if (btn.dataset.orig === undefined) btn.dataset.orig = btn.textContent;
            btn.textContent = msg;
            btn.style.opacity = isError ? '1' : '0.85';
            clearTimeout(btn._flashT);
            btn._flashT = setTimeout(function () {
                btn.textContent = btn.dataset.orig;
                btn.style.opacity = '';
            }, 1400);
        }

        // Copy plain text. Prefers the async Clipboard API; falls back to a hidden
        // textarea + execCommand for insecure contexts (http, older browsers).
        function copyText(text, btn) {
            function ok()  { flash(btn, '✓ Copiado'); }
            function fail() { flash(btn, '⛔ No se pudo copiar', true); }
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(text).then(ok, function () { legacyCopy(text) ? ok() : fail(); });
            } else {
                legacyCopy(text) ? ok() : fail();
            }
        }
        function legacyCopy(text) {
            try {
                var ta = document.createElement('textarea');
                ta.value = text;
                ta.style.cssText = 'position:fixed;top:-1000px;left:-1000px;';
                document.body.appendChild(ta);
                ta.select();
                var done = document.execCommand('copy');
                document.body.removeChild(ta);
                return done;
            } catch (e) { return false; }
        }

        // Copy a single image to the clipboard. The browser clipboard only reliably
        // holds ONE image and only as PNG, so we fetch the bytes, redraw them onto a
        // canvas to force PNG, and hand navigator.clipboard.write a *promise* — Safari
        // and Chrome both accept a promised ClipboardItem, which keeps the copy inside
        // the click gesture even though the fetch/convert is async. Needs a secure
        // context (https/localhost) and the image host to allow CORS.
        function blobToPng(blob) {
            if (blob.type === 'image/png') return Promise.resolve(blob);
            return new Promise(function (resolve, reject) {
                var img = new Image();
                var objURL = URL.createObjectURL(blob);
                img.onload = function () {
                    var canvas = document.createElement('canvas');
                    canvas.width  = img.naturalWidth  || img.width;
                    canvas.height = img.naturalHeight || img.height;
                    canvas.getContext('2d').drawImage(img, 0, 0);
                    URL.revokeObjectURL(objURL);
                    canvas.toBlob(function (b) { b ? resolve(b) : reject(new Error('toBlob')); }, 'image/png');
                };
                img.onerror = function () { URL.revokeObjectURL(objURL); reject(new Error('img load')); };
                img.src = objURL;
            });
        }
        function copyImage(url, btn) {
            if (!navigator.clipboard || !window.ClipboardItem) {
                // No image clipboard support → open it so the user can copy/save manually.
                flash(btn, '↗ Abriendo…', true);
                window.open(url, '_blank');
                return;
            }
            flash(btn, '…', false);
            var pngPromise = fetch(url, { mode: 'cors' })
                .then(function (r) { if (!r.ok) throw new Error('fetch ' + r.status); return r.blob(); })
                .then(blobToPng);
            navigator.clipboard.write([new ClipboardItem({ 'image/png': pngPromise })])
                .then(function () { flash(btn, '✓ Foto copiada'); })
                .catch(function () {
                    // CORS / unsupported → fall back to opening the image in a new tab.
                    flash(btn, '↗ Abriendo…', true);
                    window.open(url, '_blank');
                });
        }

        // --- Button factories ----------------------------------------------------
        function pill(label, opts) {
            opts = opts || {};
            var b = document.createElement('button');
            b.type = 'button';
            b.style.cssText = 'display:inline-flex;align-items:center;gap:6px;font-size:13px;' +
                (opts.thumb ? 'padding:3px 12px 3px 4px;' : 'padding:6px 12px;') +
                'border-radius:9999px;border:1px solid #d1d5db;background:' +
                (opts.accent || '#fff') + ';color:' + (opts.color || '#374151') + ';cursor:pointer;';
            if (opts.thumb) {
                var img = document.createElement('img');
                img.src = opts.thumb;
                img.style.cssText = 'width:24px;height:24px;object-fit:cover;border-radius:9999px;flex:0 0 auto;border:1px solid #e5e7eb;';
                b.appendChild(img);
            }
            var span = document.createElement('span');
            span.textContent = label;
            b.appendChild(span);
            if (opts.onClick) b.addEventListener('click', function () { opts.onClick(b); });
            return b;
        }

        function productCard(label, opts) {
            opts = opts || {};
            var b = document.createElement('button');
            b.type = 'button';
            b.style.cssText = 'display:flex;flex-direction:column;align-items:center;gap:5px;width:92px;padding:7px;' +
                'border-radius:10px;cursor:pointer;border:2px solid ' + (opts.active ? '#374151' : '#e5e7eb') + ';' +
                'background:' + (opts.active ? '#f9fafb' : '#fff') + ';';
            var thumb = document.createElement('div');
            thumb.style.cssText = 'width:74px;height:74px;border-radius:8px;overflow:hidden;background:#f3f4f6;' +
                'display:flex;align-items:center;justify-content:center;flex:0 0 auto;font-size:26px;';
            if (opts.thumb) {
                var img = document.createElement('img');
                img.src = opts.thumb;
                img.style.cssText = 'width:100%;height:100%;object-fit:cover;';
                thumb.appendChild(img);
            } else {
                thumb.textContent = opts.icon || '🏷️';
            }
            b.appendChild(thumb);
            var span = document.createElement('span');
            span.textContent = label;
            span.style.cssText = 'font-size:12px;line-height:1.15;text-align:center;word-break:break-word;color:' +
                (opts.active ? '#111827' : '#374151') + ';width:100%;' +
                'display:-webkit-box;-webkit-line-clamp:3;-webkit-box-orient:vertical;overflow:hidden;height:41.4px;';
            span.title = label;
            b.appendChild(span);
            if (opts.onClick) b.addEventListener('click', function () { opts.onClick(b); });
            return b;
        }

        function shortName(catName, prodName) {
            var stem = function (w) { return w.toLowerCase().replace(/(es|s)$/, ''); };
            var catStem = stem((catName || '').trim().split(/\s+/)[0] || '');
            var words = (prodName || '').trim().split(/\s+/);
            if (catStem && words.length > 1 && stem(words[0]) === catStem) {
                return words.slice(1).join(' ');
            }
            return prodName;
        }

        // --- variant data helpers (mirrors the inbox drill) ----------------------
        function inScope(prod, filters) {
            return prod.variants.filter(function (v) {
                return (filters || []).every(function (f) { return String(v.tags[f.tag]) === String(f.value); });
            });
        }
        function distinctValues(variants, tag) {
            var seen = {}, out = [];
            variants.forEach(function (v) {
                var val = v.tags[tag];
                if (val === undefined || val === null || val === '') return;
                if (!seen[val]) { seen[val] = true; out.push(val); }
            });
            return out;
        }
        function isPlain(val) {
            if (val === null || val === undefined) return false;
            if (typeof val !== 'string') return typeof val !== 'object';
            var s = val.trim();
            if (s === '') return false;
            if (s.charAt(0) === '[' || s.charAt(0) === '{') {
                try { var parsed = JSON.parse(s); if (parsed && typeof parsed === 'object') return false; }
                catch (e) { /* not JSON → plain text */ }
            }
            return true;
        }
        function scanTags(variants, filters) {
            var seen = {}, names = [];
            variants.forEach(function (v) {
                Object.keys(v.tags).forEach(function (tag) {
                    if (seen[tag]) return;
                    if (tag.indexOf('medidas_') === 0) return;
                    if ((filters || []).some(function (f) { return f.tag === tag; })) return;
                    seen[tag] = true; names.push(tag);
                });
            });
            names = names.filter(function (tag) {
                return variants.every(function (v) {
                    var val = v.tags[tag];
                    return val === undefined || val === null || val === '' || isPlain(val);
                });
            });
            names.sort();
            return names;
        }
        function prettyLabel(tag) {
            var s = tag.replace(/_/g, ' ');
            return s.charAt(0).toUpperCase() + s.slice(1);
        }
        function prettyMeasure(tag) {
            var s = tag.replace(/^medidas_/, '');
            s = s.replace(/_(cm|mm|m|in|kg|g|lb|ml|l)$/, ' ($1)');
            return s.replace(/_/g, ' ');
        }
        function money(v) {
            v = Number(v);
            return (v % 1 === 0)
                ? v.toLocaleString('en-US')
                : v.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }
        function urlsFromTags(tagsObj) {
            var out = [];
            Object.keys(tagsObj).forEach(function (k) {
                var val = tagsObj[k];
                if (typeof val !== 'string') return;
                var s = val.trim();
                if (s.charAt(0) !== '[') return;
                try {
                    var arr = JSON.parse(s);
                    if (Array.isArray(arr)) arr.forEach(function (u) {
                        if (typeof u === 'string' && /^https?:\/\//.test(u)) out.push(u);
                    });
                } catch (e) { /* not JSON → ignore */ }
            });
            return out;
        }
        function flatMeasures(obj) {
            var out = [];
            Object.keys(obj).forEach(function (key) {
                if (obj[key] !== '' && obj[key] != null && typeof obj[key] !== 'object') {
                    out.push(prettyLabel(key) + ': ' + obj[key]);
                }
            });
            return out;
        }
        function measuresOf(v) {
            var parts = [];
            Object.keys(v.tags).forEach(function (tag) {
                var val = v.tags[tag];
                if (tag.indexOf('medidas_') === 0) {
                    if (val !== '' && val != null) parts.push(prettyMeasure(tag) + ': ' + val);
                    return;
                }
                if (typeof val !== 'string') return;
                var s = val.trim();
                if (s.charAt(0) !== '{') return;
                try {
                    var obj = JSON.parse(s);
                    if (!obj || typeof obj !== 'object' || Array.isArray(obj)) return;
                    Object.keys(obj).forEach(function (key) {
                        var sub = obj[key];
                        if (sub && typeof sub === 'object' && !Array.isArray(sub)) {
                            var inner = flatMeasures(sub);
                            if (inner.length) parts.push(prettyLabel(key) + ' — ' + inner.join(', '));
                        } else if (sub !== '' && sub != null) {
                            parts.push(prettyLabel(key) + ': ' + sub);
                        }
                    });
                } catch (e) { /* not JSON → ignore */ }
            });
            return parts;
        }
        function hasMedidas(variants) {
            return variants.some(function (v) { return measuresOf(v).length > 0; });
        }
        function medidasText(prod, variants) {
            var blocks = [];
            if (prod.pivot) {
                var seen = {};
                variants.forEach(function (v) {
                    var pv = v.pivot_valor;
                    if (pv === null || pv === undefined || seen[pv]) return;
                    seen[pv] = true;
                    var parts = measuresOf(v);
                    if (!parts.length) return;
                    var lines = ['*' + (prod.pivot_label || prod.pivot) + ' ' + pv + '*'];
                    parts.forEach(function (part) { lines.push('• ' + part); });
                    blocks.push(lines.join('\n'));
                });
            } else if (variants[0]) {
                var single = measuresOf(variants[0]).map(function (part) { return '• ' + part; });
                if (single.length) blocks.push(single.join('\n'));
            }
            if (!blocks.length) return null;
            return '📏 Medidas de ' + prod.nombre + ':\n\n' + blocks.join('\n\n');
        }
        function priceText(prod, variants) {
            var priced = variants.filter(function (v) { return v.precio !== null && v.precio !== undefined; });
            if (!priced.length) return null;
            var prices = priced.map(function (v) { return v.precio; });
            var min = Math.min.apply(null, prices), max = Math.max.apply(null, prices);

            // Single price across every size → one clean line.
            if (min === max) {
                var line = '💰 ' + prod.nombre + ': Q' + money(min);
                if (variants.length && variants.every(function (v) { return v.agotado; })) line += ' (agotado)';
                return line;
            }

            // Otherwise list each size with its own price.
            var lines = [], seen = {};
            priced.forEach(function (v) {
                var pv = v.pivot_valor;
                var label = (prod.pivot && pv !== null && pv !== undefined && pv !== '')
                    ? (prod.pivot_label || prod.pivot) + ' ' + pv
                    : (v.nombre || '');
                if (label && seen[label]) return;   // one price per size
                if (label) seen[label] = true;
                var l = '• ' + (label ? label + ': ' : '') + 'Q' + money(v.precio);
                if (v.agotado) l += ' (agotado)';
                lines.push(l);
            });
            return '💰 ' + prod.nombre + ':\n' + lines.join('\n');
        }
        function scopeImages(prod, variants, narrowed) {
            if (!narrowed && prod.product_images && prod.product_images.length) {
                return prod.product_images.slice(0, 10);
            }
            var imgs = [];
            variants.forEach(function (v) {
                (v.images || []).forEach(function (u) { imgs.push(u); });
                urlsFromTags(v.tags).forEach(function (u) { imgs.push(u); });
            });
            if (!imgs.length) imgs = (prod.product_images || []).slice();
            var seen = {}, out = [];
            imgs.forEach(function (u) { if (u && !seen[u]) { seen[u] = true; out.push(u); } });
            return out.slice(0, 10);
        }
        function coverImage(prod) {
            if (prod.product_images && prod.product_images.length) return prod.product_images[0];
            for (var i = 0; i < prod.variants.length; i++) {
                var v = prod.variants[i];
                var imgs = (v.images || []).concat(urlsFromTags(v.tags));
                if (imgs.length) return imgs[0];
            }
            return null;
        }

        // --- rendering -----------------------------------------------------------
        var state = { cat: null, prod: null, filters: [], pendingTag: null };

        function section(title) {
            var wrap = document.createElement('div');
            wrap.style.cssText = 'margin-top:10px;padding-top:10px;border-top:2px solid #e5e7eb;';
            var head = document.createElement('div');
            head.style.cssText = 'font-size:11px;text-transform:uppercase;letter-spacing:.05em;color:#9ca3af;margin-bottom:4px;';
            head.textContent = title;
            var row = document.createElement('div');
            row.className = 'flex flex-wrap';
            row.style.cssText = 'gap:8px;align-items:flex-start;';
            wrap.appendChild(head); wrap.appendChild(row);
            qrButtons.appendChild(wrap);
            return row;
        }

        function renderCats() {
            qrCats.innerHTML = '';
            MENU.forEach(function (g, i) {
                var active = state.cat === i;
                var t = document.createElement('button');
                t.type = 'button';
                t.textContent = g.categoria + ' (' + g.products.length + ')';
                t.style.cssText = 'font-size:13px;padding:10px 16px;margin-bottom:-1px;background:' +
                    (active ? '#eef2ff' : 'none') + ';border:none;border-top-left-radius:8px;border-top-right-radius:8px;' +
                    'border-bottom:3px solid ' + (active ? '#3730a3' : 'transparent') + ';' +
                    'color:' + (active ? '#3730a3' : '#6b7280') + ';font-weight:' + (active ? '700' : '500') + ';cursor:pointer;';
                t.addEventListener('click', function () { openCategory(i); });
                qrCats.appendChild(t);
            });
        }
        function openCategory(i) { state.cat = i; state.prod = null; state.filters = []; state.pendingTag = null; render(); }
        function openProduct(j) { state.prod = j; state.filters = []; state.pendingTag = null; render(); }

        // A large tap-to-copy photo tile (big thumbnail so it's easy to see).
        function photoCard(url, i) {
            var b = document.createElement('button');
            b.type = 'button';
            b.title = 'Copiar esta foto';
            b.style.cssText = 'display:flex;flex-direction:column;align-items:center;gap:5px;padding:6px;' +
                'border-radius:12px;cursor:pointer;border:2px solid #fde68a;background:#fffbeb;';
            var img = document.createElement('img');
            img.src = url;
            img.style.cssText = 'width:120px;height:120px;object-fit:cover;border-radius:10px;border:1px solid #e5e7eb;';
            b.appendChild(img);
            var cap = document.createElement('span');
            cap.textContent = '📷 Foto ' + (i + 1);
            cap.style.cssText = 'font-size:12px;color:#92400e;font-weight:600;';
            b.appendChild(cap);
            // Pass the caption as the flash target so the "✓ Foto copiada" feedback
            // shows under the image without hiding the thumbnail.
            b.addEventListener('click', function () { copyImage(url, cap); });
            return b;
        }

        function buildProducts(row) {
            var g = MENU[state.cat];

            g.products.forEach(function (p, j) {
                row.appendChild(productCard(shortName(g.categoria, p.nombre), {
                    active: state.prod === j,
                    thumb: coverImage(p),
                    onClick: function () { openProduct(j); }
                }));
            });
        }

        function backOne() {
            if (state.pendingTag) { state.pendingTag = null; }
            else if (state.filters.length) { state.filters.pop(); }
            render();
        }

        function buildDetail(row) {
            var prod = MENU[state.cat].products[state.prod];
            var variants = inScope(prod, state.filters);

            if (state.pendingTag || state.filters.length) {
                row.appendChild(pill('← Atrás', { accent: '#6b7280', color: '#fff', onClick: backOne }));
            }

            if (state.pendingTag) {
                distinctValues(variants, state.pendingTag).forEach(function (val) {
                    row.appendChild(pill(val, {
                        accent: '#f5f3ff', color: '#5b21b6',
                        onClick: function () { state.filters.push({ tag: state.pendingTag, value: val }); state.pendingTag = null; render(); }
                    }));
                });
                return;
            }

            if (!state.filters.length) {
                prod.product_tags.forEach(function (t) {
                    row.appendChild(pill(t.label, {
                        accent: '#eef2ff', color: '#3730a3',
                        onClick: function (btn) { copyText(t.text, btn); }
                    }));
                });
            }

            scanTags(variants, state.filters).forEach(function (tag) {
                var vals = distinctValues(variants, tag);
                if (vals.length === 0) return;
                if (vals.length === 1) {
                    var text = prettyLabel(tag) + ' de ' + prod.nombre + ': ' + vals[0];
                    row.appendChild(pill(prettyLabel(tag), {
                        accent: '#f5f3ff', color: '#5b21b6',
                        onClick: function (btn) { copyText(text, btn); }
                    }));
                } else {
                    row.appendChild(pill(prettyLabel(tag) + ' ▸', {
                        onClick: function () { state.pendingTag = tag; render(); }
                    }));
                }
            });

            if (hasMedidas(variants)) {
                var mt = medidasText(prod, variants);
                if (mt) {
                    row.appendChild(pill('📏 Medidas', {
                        accent: '#eff6ff', color: '#1e40af',
                        onClick: function (btn) { copyText(mt, btn); }
                    }));
                }
            }

            var pt = priceText(prod, variants);
            if (pt) {
                row.appendChild(pill('💰 Precio', {
                    accent: '#ecfdf5', color: '#065f46',
                    onClick: function (btn) { copyText(pt, btn); }
                }));
            }

            // Photos go in their own row BELOW the other options, as big tiles.
            var imgs = scopeImages(prod, variants, state.filters.length > 0);
            if (imgs.length) {
                var photoRow = section('Fotos (toca para copiar)');
                imgs.forEach(function (url, i) { photoRow.appendChild(photoCard(url, i)); });
            }
        }

        function detailLabel() {
            var prod = MENU[state.cat].products[state.prod];
            var label = prod.nombre;
            if (state.filters.length) label += ' · ' + state.filters.map(function (f) { return f.value; }).join(' · ');
            if (state.pendingTag) label += ' · elige ' + prettyLabel(state.pendingTag);
            return label;
        }

        function render() {
            renderCats();
            qrButtons.innerHTML = '';
            if (state.cat !== null) buildProducts(section(MENU[state.cat].categoria + ' · productos'));
            if (state.prod !== null) buildDetail(section(detailLabel()));
        }

        render();
    })();
    </script>
    @endif
</x-app-layout>
