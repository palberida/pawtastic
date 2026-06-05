<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight flex items-center" style="gap:10px;">
                @include('metabot.inbox._avatar', ['name' => $name ?? null, 'phone' => $phone, 'size' => 32])
                <span>@if(!empty($name)){{ $name }} <span class="text-sm text-gray-400 font-normal">+{{ $phone }}</span>@else+{{ $phone }}@endif</span>
            </h2>
            <a href="{{ route('metabot.inbox.index') }}" class="text-sm text-blue-600 hover:underline">← Bandeja</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-2 lg:px-4">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    @if (session('error'))
                        <div class="mb-4 text-red-600">{{ session('error') }}</div>
                    @endif
                    @if (session('success'))
                        <div class="mb-4 text-green-600">{{ session('success') }}</div>
                    @endif

                    <div id="thread" class="max-h-96 overflow-y-auto border border-gray-100 rounded-md p-3 bg-gray-50">
                        @include('metabot.inbox._thread')
                    </div>

                    {{-- Live 24h customer-service-window indicator (filled by JS). --}}
                    <div id="wa-window-banner" class="mt-3 px-3 py-2 rounded-md text-sm" style="display:none;"></div>

                    @if(!empty($quickMenu))
                        <div class="mt-4 pt-4 border-t border-gray-200">
                            <div class="flex items-center justify-between mb-2">
                                <label class="block text-sm font-medium text-gray-700">Respuestas rápidas</label>
                                <button type="button" id="qr-back" style="display:none;font-size:13px;color:#2563eb;background:none;border:none;cursor:pointer;">← Atrás</button>
                            </div>
                            <div>
                                <div style="font-size:11px;text-transform:uppercase;letter-spacing:.05em;color:#9ca3af;margin-bottom:4px;">Categorías</div>
                                <div id="qr-cats" class="flex flex-wrap" style="gap:8px;"></div>
                            </div>
                            <div id="qr-buttons"></div>
                            <div id="qr-photos" style="display:none;" class="mt-3"></div>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('metabot.inbox.reply', ['phone' => $phone]) }}" class="mt-4">
                        @csrf
                        <textarea name="body" id="reply-body" rows="2" maxlength="4096" required placeholder="Escribe una respuesta..." class="block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">{{ old('body') }}</textarea>
                        <div class="mt-2 flex justify-between items-center">
                            <span class="text-xs text-gray-400">Solo se puede responder dentro de las 24h del último mensaje del cliente.</span>
                            <button type="submit" id="reply-send" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 transition">Enviar</button>
                        </div>
                    </form>

                    <form method="POST" action="{{ route('metabot.inbox.image', ['phone' => $phone]) }}" enctype="multipart/form-data" class="mt-4 pt-4 border-t border-gray-200">
                        @csrf
                        <label for="image" class="block text-sm font-medium text-gray-700">Enviar una imagen</label>
                        <input type="file" name="image" id="image" accept="image/jpeg,image/png" required class="mt-1 block w-full text-sm text-gray-600">
                        <input type="text" name="caption" maxlength="1024" placeholder="Descripción (opcional)" class="mt-2 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <div class="mt-2 flex justify-between items-center">
                            <span class="text-xs text-gray-400">JPG o PNG, máx 5MB. Solo dentro de la ventana de 24h.</span>
                            <button type="submit" id="image-send" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 transition">Enviar imagen</button>
                        </div>
                    </form>

                    @if($templates->isNotEmpty())
                        <div class="mt-6 pt-4 border-t border-gray-200">
                            <label for="template_id" class="block text-sm font-medium text-gray-700">Reabrir conversación con una plantilla</label>
                            <p class="text-xs text-gray-400 mb-2">Úsala cuando ya pasaron 24h del último mensaje del cliente. Cada envío tiene costo (mensaje de plantilla de Meta).</p>
                            <form method="POST" action="{{ route('metabot.inbox.template', ['phone' => $phone]) }}" onsubmit="return confirm('Se enviará una plantilla pagada para reabrir la conversación. ¿Continuar?');">
                                @csrf
                                <select name="template_id" id="template_id" required class="block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    @foreach($templates as $t)
                                        <option value="{{ $t->id }}" data-body="{{ $t->body_preview }}">{{ $t->label ?: $t->name }}</option>
                                    @endforeach
                                </select>
                                <p id="template_preview" class="text-sm text-gray-600 mt-2 italic"></p>
                                <div class="mt-2 text-right">
                                    <button type="submit" class="bg-amber-500 text-white px-4 py-2 rounded hover:bg-amber-600 transition">Enviar plantilla</button>
                                </div>
                            </form>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <script>
    (function () {
        var url    = "{{ route('metabot.inbox.messages', ['phone' => $phone]) }}";
        var thread = document.getElementById('thread');
        function scrollBottom() { thread.scrollTop = thread.scrollHeight; }
        function poll() {
            fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function (r) { return r.text(); })
                .then(function (html) { thread.innerHTML = html; scrollBottom(); updateWindow(); })
                .catch(function () {});
        }

        // --- 24h customer-service window ---
        // WhatsApp only delivers free-form messages within 24h of the customer's
        // last inbound. WA.open gates the free-form send controls; templates are the
        // only way to message outside it. The window resets whenever a new inbound
        // arrives (the poll re-emits #wa-window-data and we re-read it).
        var WINDOW_MS = 24 * 60 * 60 * 1000;
        var WA        = { open: true };
        var banner    = document.getElementById('wa-window-banner');
        var replySend = document.getElementById('reply-send');
        var imageSend = document.getElementById('image-send');

        function setBanner(bg, color, text) {
            if (!banner) return;
            banner.style.display = '';
            banner.style.background = bg;
            banner.style.color = color;
            banner.textContent = text;
        }
        function fmtRemaining(ms) {
            var total = Math.floor(ms / 60000), h = Math.floor(total / 60), m = total % 60;
            return h > 0 ? (h + 'h ' + m + 'm') : (m + 'm');
        }
        function applyWindowDisabled() {
            var buttons = [replySend, imageSend];
            document.querySelectorAll('[data-wa-send]').forEach(function (b) { buttons.push(b); });
            buttons.forEach(function (b) {
                if (!b) return;
                b.disabled = !WA.open;
                b.style.opacity = WA.open ? '' : '0.5';
                b.style.cursor = WA.open ? '' : 'not-allowed';
            });
        }
        function updateWindow() {
            var el = document.getElementById('wa-window-data');
            var at = el ? parseInt(el.getAttribute('data-at'), 10) : NaN;
            if (!at) {
                WA.open = false;
                setBanner('#fee2e2', '#991b1b', '⛔ El cliente aún no ha escrito — no puedes enviar mensajes libres. Inícialo con una plantilla.');
            } else {
                var expiry = at * 1000 + WINDOW_MS;
                var remaining = expiry - Date.now();
                var when = new Date(expiry).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                if (remaining <= 0) {
                    WA.open = false;
                    setBanner('#fee2e2', '#991b1b', '⛔ Ventana de 24h cerrada — los mensajes libres NO se entregarán. Reábrela con una plantilla.');
                } else if (remaining <= 2 * WINDOW_MS / 24) { // < 2h left
                    WA.open = true;
                    setBanner('#fef3c7', '#92400e', '⚠ Ventana de 24h por cerrar — quedan ' + fmtRemaining(remaining) + ' (vence ' + when + ').');
                } else {
                    WA.open = true;
                    setBanner('#ecfdf5', '#065f46', '✅ Ventana de 24h abierta — quedan ' + fmtRemaining(remaining) + ' (vence ' + when + ').');
                }
            }
            applyWindowDisabled();
        }

        scrollBottom();
        updateWindow();
        setInterval(poll, 7000);
        setInterval(updateWindow, 30000); // tick the countdown / flip to closed at expiry

        var sel = document.getElementById('template_id');
        var prev = document.getElementById('template_preview');
        if (sel && prev) {
            function showPreview() {
                var opt = sel.options[sel.selectedIndex];
                prev.textContent = opt ? (opt.getAttribute('data-body') || '') : '';
            }
            sel.addEventListener('change', showPreview);
            showPreview();
        }

        // --- Quick replies: Categorías → Productos → drill de tags (narrowing) ---
        // Level 2 scans the in-scope variants' tags. A tag with one value across the
        // scope is terminal (click → prefill); a tag with several values opens its
        // values, and picking one narrows the scope and re-scans. medidas_* collapse
        // into one "Medidas", image_* into one "Fotos"; both are scope-aware.
        var MENU       = @json($quickMenu ?? []);
        var CSRF       = "{{ csrf_token() }}";
        var PHOTOS_URL     = "{{ route('metabot.inbox.quickphotos', ['phone' => $phone]) }}";
        var PHOTOS_CAT_URL = "{{ route('metabot.inbox.quickcatphotos', ['phone' => $phone]) }}";
        var qrCats     = document.getElementById('qr-cats');
        var qrButtons  = document.getElementById('qr-buttons');
        var qrBack     = document.getElementById('qr-back');
        var qrPhotos   = document.getElementById('qr-photos');
        var replyBox   = document.getElementById('reply-body');

        if (qrButtons) {
            // Three visible rows: categories (always), products (when a category is
            // picked), and a single "detail" row for everything inside a product. The
            // category and product rows cascade (stay visible); the detail row REPLACES
            // its buttons as you narrow. filters/pendingTag drive that detail row.
            var state = { cat: null, prod: null, filters: [], pendingTag: null };

            function pill(label, opts) {
                opts = opts || {};
                var b = document.createElement('button');
                b.type = 'button';
                b.textContent = label;
                b.disabled = !!opts.disabled;
                b.style.cssText = 'font-size:13px;padding:6px 12px;border-radius:9999px;border:1px solid #d1d5db;background:' +
                    (opts.disabled ? '#f3f4f6' : (opts.accent || '#fff')) + ';color:' +
                    (opts.disabled ? '#9ca3af' : (opts.color || '#374151')) + ';cursor:' +
                    (opts.disabled ? 'not-allowed' : 'pointer') + ';';
                if (!opts.disabled && opts.onClick) b.addEventListener('click', opts.onClick);
                return b;
            }

            function clearPhotos() { qrPhotos.style.display = 'none'; qrPhotos.innerHTML = ''; }

            function fillReply(text) {
                if (!replyBox || !text) return;
                replyBox.value = text;
                replyBox.focus();
                replyBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }

            // --- helpers over variant data ---
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

            // A value is "plain" if it's a simple scalar — not a JSON array/object.
            // Tags carrying structured values (e.g. a list) aren't drillable groups.
            function isPlain(val) {
                if (val === null || val === undefined) return false;
                if (typeof val !== 'string') return typeof val !== 'object';
                var s = val.trim();
                if (s === '') return false;
                if (s.charAt(0) === '[' || s.charAt(0) === '{') {
                    try {
                        var parsed = JSON.parse(s);
                        if (parsed && typeof parsed === 'object') return false; // array or object
                    } catch (e) { /* not valid JSON → it's plain text */ }
                }
                return true;
            }

            // Tag names present across the scope, minus those already chosen and the
            // medidas_* family (handled by one Medidas button). image_* never reach
            // here — the catalog strips them, and photos get their own button. Only
            // tags whose values are plain text become drillable groups.
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

            // URLs carried inside a tag whose value is a JSON array (the `images`
            // convention), e.g. images: ["https://…","https://…"].
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

            // Measurement "label: value" parts for a variant, from both medidas_*
            // tags and any tag whose value is a JSON object (the `medidas` convention,
            // e.g. medidas: {"cuello":"35-41 cm","largo":"46 cm"}).
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
                        if (obj && typeof obj === 'object' && !Array.isArray(obj)) {
                            Object.keys(obj).forEach(function (key) {
                                if (obj[key] !== '' && obj[key] != null) parts.push(prettyLabel(key) + ': ' + obj[key]);
                            });
                        }
                    } catch (e) { /* not JSON → ignore */ }
                });
                return parts;
            }

            function hasMedidas(variants) {
                return variants.some(function (v) { return measuresOf(v).length > 0; });
            }

            function medidasText(prod, variants) {
                var lines = [];
                if (prod.pivot) {
                    var seen = {};
                    variants.forEach(function (v) {
                        var pv = v.pivot_valor;
                        if (pv === null || pv === undefined || seen[pv]) return;
                        seen[pv] = true;
                        var parts = measuresOf(v);
                        if (parts.length) {
                            lines.push('• ' + (prod.pivot_label || prod.pivot) + ' ' + pv + ' — ' + parts.join(', '));
                        }
                    });
                } else if (variants[0]) {
                    measuresOf(variants[0]).forEach(function (part) { lines.push('• ' + part); });
                }
                if (!lines.length) return null;
                return '📏 Medidas de ' + prod.nombre + ':\n' + lines.join('\n');
            }

            function priceText(prod, variants) {
                var prices = variants.map(function (v) { return v.precio; })
                    .filter(function (x) { return x !== null && x !== undefined; });
                if (!prices.length) return null;
                var min = Math.min.apply(null, prices), max = Math.max.apply(null, prices);
                var line = (min === max)
                    ? '💰 ' + prod.nombre + ': Q' + money(min)
                    : '💰 ' + prod.nombre + ': desde Q' + money(min) + ' hasta Q' + money(max);
                if (variants.length && variants.every(function (v) { return v.agotado; })) line += ' (agotado)';
                return line;
            }

            function scopeImages(prod, variants) {
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

            // One representative photo for a product: product-level first, else the
            // first variant that has one (image_N or images-array tag).
            function coverImage(prod) {
                if (prod.product_images && prod.product_images.length) return prod.product_images[0];
                for (var i = 0; i < prod.variants.length; i++) {
                    var v = prod.variants[i];
                    var imgs = (v.images || []).concat(urlsFromTags(v.tags));
                    if (imgs.length) return imgs[0];
                }
                return null;
            }

            // images: URLs to preview; action: the POST endpoint; extraFields: extra
            // hidden inputs (e.g. product_id or product_ids[]) the endpoint needs.
            function showPhotos(images, action, extraFields) {
                // Mutable selection so the staff can drop photos before sending.
                var selected = images.slice();

                function draw() {
                    qrPhotos.innerHTML = '';

                    var note = document.createElement('p');
                    note.style.cssText = 'font-size:12px;color:#6b7280;margin-bottom:6px;';
                    note.textContent = selected.length
                        ? 'Se enviarán estas fotos al cliente (toca la ✕ para quitar):'
                        : 'No quedan fotos seleccionadas.';
                    qrPhotos.appendChild(note);

                    var gallery = document.createElement('div');
                    gallery.style.cssText = 'display:flex;flex-wrap:wrap;gap:6px;margin-bottom:8px;';
                    selected.forEach(function (url, idx) {
                        var cell = document.createElement('div');
                        cell.style.cssText = 'position:relative;width:64px;height:64px;';

                        var img = document.createElement('img');
                        img.src = url;
                        img.style.cssText = 'width:64px;height:64px;object-fit:cover;border-radius:6px;border:1px solid #e5e7eb;';
                        cell.appendChild(img);

                        var rm = document.createElement('button');
                        rm.type = 'button';
                        rm.textContent = '✕';
                        rm.title = 'Quitar foto';
                        rm.style.cssText = 'position:absolute;top:-6px;right:-6px;width:18px;height:18px;line-height:16px;' +
                            'padding:0;border-radius:9999px;border:1px solid #fff;background:#dc2626;color:#fff;' +
                            'font-size:11px;cursor:pointer;';
                        rm.addEventListener('click', function () { selected.splice(idx, 1); draw(); });
                        cell.appendChild(rm);

                        gallery.appendChild(cell);
                    });
                    qrPhotos.appendChild(gallery);

                    if (!selected.length) return;

                    var form = document.createElement('form');
                    form.method = 'POST';
                    form.action = action;
                    var html = '<input type="hidden" name="_token" value="' + CSRF + '">';
                    (extraFields || []).forEach(function (f) {
                        html += '<input type="hidden" name="' + f.name + '" value="' + String(f.value).replace(/"/g, '&quot;') + '">';
                    });
                    selected.forEach(function (url) {
                        html += '<input type="hidden" name="images[]" value="' + url.replace(/"/g, '&quot;') + '">';
                    });
                    form.innerHTML = html;
                    var send = document.createElement('button');
                    send.type = 'submit';
                    send.setAttribute('data-wa-send', '1');
                    send.textContent = 'Enviar ' + selected.length + ' foto(s)';
                    send.style.cssText = 'font-size:13px;padding:6px 14px;border-radius:6px;border:none;background:#f59e0b;color:#fff;cursor:pointer;';
                    send.disabled = !WA.open;
                    if (!WA.open) { send.style.opacity = '0.5'; send.style.cursor = 'not-allowed'; }
                    form.appendChild(send);
                    qrPhotos.appendChild(form);
                }

                draw();
                qrPhotos.style.display = '';
                qrPhotos.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }

            // The category row stays visible at all times; the active one is filled in.
            function renderCats() {
                qrCats.innerHTML = '';
                MENU.forEach(function (g, i) {
                    var active = state.cat === i;
                    qrCats.appendChild(pill(g.categoria + ' (' + g.products.length + ')', {
                        accent: active ? '#3730a3' : '#eef2ff',
                        color: active ? '#fff' : '#3730a3',
                        onClick: function () { openCategory(i); }
                    }));
                });
            }

            function openCategory(i) {
                state.cat = i; state.prod = null; state.filters = []; state.pendingTag = null;
                render();
            }
            function openProduct(j) {
                state.prod = j; state.filters = []; state.pendingTag = null;
                render();
            }

            // A divided, labelled section wrapper. Returns the inner row to fill.
            function section(title) {
                var wrap = document.createElement('div');
                wrap.style.cssText = 'margin-top:10px;padding-top:10px;border-top:2px solid #e5e7eb;';
                var head = document.createElement('div');
                head.style.cssText = 'font-size:11px;text-transform:uppercase;letter-spacing:.05em;color:#9ca3af;margin-bottom:4px;';
                head.textContent = title;
                var row = document.createElement('div');
                row.className = 'flex flex-wrap';
                row.style.cssText = 'gap:8px;';
                wrap.appendChild(head); wrap.appendChild(row);
                qrButtons.appendChild(wrap);
                return row;
            }

            // Row 2 — products of the active category (cascades; stays visible).
            function buildProducts(row) {
                var g = MENU[state.cat];

                var covers = [], pids = [];
                g.products.forEach(function (p) {
                    pids.push(p.id);
                    var u = coverImage(p);
                    if (u) covers.push(u);
                });
                if (covers.length) {
                    row.appendChild(pill('📷 Fotos de la categoría (' + covers.length + ')', {
                        accent: '#fef3c7', color: '#92400e',
                        onClick: function () {
                            showPhotos(covers, PHOTOS_CAT_URL, pids.map(function (id) { return { name: 'product_ids[]', value: id }; }));
                        }
                    }));
                }
                g.products.forEach(function (p, j) {
                    var active = state.prod === j;
                    row.appendChild(pill(p.nombre, {
                        accent: active ? '#374151' : '#fff', color: active ? '#fff' : '#374151',
                        onClick: function () { openProduct(j); }
                    }));
                });
            }

            // Row 3 — everything inside the product, in ONE replacing row. Shows either
            // the value choices for a tag being narrowed, or the current scan buttons.
            function buildDetail(row) {
                var prod = MENU[state.cat].products[state.prod];
                var variants = inScope(prod, state.filters);

                // Narrowing a multi-value tag: show its values (replaces the scan).
                if (state.pendingTag) {
                    distinctValues(variants, state.pendingTag).forEach(function (val) {
                        row.appendChild(pill(val, {
                            accent: '#f5f3ff', color: '#5b21b6',
                            onClick: function () { state.filters.push({ tag: state.pendingTag, value: val }); state.pendingTag = null; render(); }
                        }));
                    });
                    return;
                }

                // Product-level tags, only before any narrowing. Terminal (prefill).
                if (!state.filters.length) {
                    prod.product_tags.forEach(function (t) {
                        row.appendChild(pill(t.label, {
                            accent: '#eef2ff', color: '#3730a3',
                            onClick: function () { fillReply(t.text); }
                        }));
                    });
                }

                // Scanned variant tags: one value → terminal prefill; many → drill deeper.
                scanTags(variants, state.filters).forEach(function (tag) {
                    var vals = distinctValues(variants, tag);
                    if (vals.length === 0) return;
                    if (vals.length === 1) {
                        var text = prettyLabel(tag) + ' de ' + prod.nombre + ': ' + vals[0];
                        row.appendChild(pill(prettyLabel(tag), {
                            accent: '#f5f3ff', color: '#5b21b6',
                            onClick: function () { fillReply(text); }
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
                            onClick: function () { fillReply(mt); }
                        }));
                    }
                }

                var pt = priceText(prod, variants);
                if (pt) {
                    row.appendChild(pill('💰 Precio', {
                        accent: '#ecfdf5', color: '#065f46',
                        onClick: function () { fillReply(pt); }
                    }));
                }

                var imgs = scopeImages(prod, variants);
                if (imgs.length) {
                    row.appendChild(pill('📷 Fotos', {
                        accent: '#fef3c7', color: '#92400e',
                        onClick: function () { showPhotos(imgs, PHOTOS_URL, [{ name: 'product_id', value: prod.id }]); }
                    }));
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
                clearPhotos();

                if (state.cat !== null) {
                    buildProducts(section(MENU[state.cat].categoria + ' · productos'));
                }
                if (state.prod !== null) {
                    buildDetail(section(detailLabel()));
                }

                qrBack.style.display = state.cat !== null ? '' : 'none';
            }

            // Back peels one level: tag values → narrowing → product → category.
            qrBack.addEventListener('click', function () {
                if (state.pendingTag) { state.pendingTag = null; }
                else if (state.filters.length) { state.filters.pop(); }
                else if (state.prod !== null) { state.prod = null; }
                else { state.cat = null; }
                render();
            });

            render();
        }
    })();
    </script>
</x-app-layout>
