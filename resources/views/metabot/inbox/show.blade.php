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

                    @if(!empty($quickMenu))
                        <div class="mt-4 pt-4 border-t border-gray-200">
                            <div class="flex items-center justify-between mb-2">
                                <label class="block text-sm font-medium text-gray-700">Respuestas rápidas</label>
                                <button type="button" id="qr-back" style="display:none;font-size:13px;color:#2563eb;background:none;border:none;cursor:pointer;">← Atrás</button>
                            </div>
                            <p id="qr-crumb" class="text-xs text-gray-400 mb-2">Elige una categoría.</p>
                            <div id="qr-buttons" class="flex flex-wrap" style="gap:8px;"></div>
                            <div id="qr-photos" style="display:none;" class="mt-3"></div>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('metabot.inbox.reply', ['phone' => $phone]) }}" class="mt-4">
                        @csrf
                        <textarea name="body" id="reply-body" rows="2" maxlength="4096" required placeholder="Escribe una respuesta..." class="block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">{{ old('body') }}</textarea>
                        <div class="mt-2 flex justify-between items-center">
                            <span class="text-xs text-gray-400">Solo se puede responder dentro de las 24h del último mensaje del cliente.</span>
                            <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 transition">Enviar</button>
                        </div>
                    </form>

                    <form method="POST" action="{{ route('metabot.inbox.image', ['phone' => $phone]) }}" enctype="multipart/form-data" class="mt-4 pt-4 border-t border-gray-200">
                        @csrf
                        <label for="image" class="block text-sm font-medium text-gray-700">Enviar una imagen</label>
                        <input type="file" name="image" id="image" accept="image/jpeg,image/png" required class="mt-1 block w-full text-sm text-gray-600">
                        <input type="text" name="caption" maxlength="1024" placeholder="Descripción (opcional)" class="mt-2 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <div class="mt-2 flex justify-between items-center">
                            <span class="text-xs text-gray-400">JPG o PNG, máx 5MB. Solo dentro de la ventana de 24h.</span>
                            <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 transition">Enviar imagen</button>
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
                .then(function (html) { thread.innerHTML = html; scrollBottom(); })
                .catch(function () {});
        }
        scrollBottom();
        setInterval(poll, 7000);

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
        var PHOTOS_URL = "{{ route('metabot.inbox.quickphotos', ['phone' => $phone]) }}";
        var qrButtons  = document.getElementById('qr-buttons');
        var qrBack     = document.getElementById('qr-back');
        var qrCrumb    = document.getElementById('qr-crumb');
        var qrPhotos   = document.getElementById('qr-photos');
        var replyBox   = document.getElementById('reply-body');

        if (qrButtons) {
            // filters: chosen {tag,value} pairs. pendingTag: a multi-value tag whose
            // values we're currently offering.
            var state = { level: 0, cat: null, prod: null, filters: [], pendingTag: null };

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
            function curProduct() { return MENU[state.cat].products[state.prod]; }

            function inScope(prod) {
                return prod.variants.filter(function (v) {
                    return state.filters.every(function (f) { return String(v.tags[f.tag]) === String(f.value); });
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
            function scanTags(variants) {
                var seen = {}, names = [];
                variants.forEach(function (v) {
                    Object.keys(v.tags).forEach(function (tag) {
                        if (seen[tag]) return;
                        if (tag.indexOf('medidas_') === 0) return;
                        if (state.filters.some(function (f) { return f.tag === tag; })) return;
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

            function showPhotos(prod, images) {
                qrPhotos.innerHTML = '';
                var note = document.createElement('p');
                note.style.cssText = 'font-size:12px;color:#6b7280;margin-bottom:6px;';
                note.textContent = 'Se enviarán estas fotos al cliente:';
                qrPhotos.appendChild(note);

                var gallery = document.createElement('div');
                gallery.style.cssText = 'display:flex;flex-wrap:wrap;gap:6px;margin-bottom:8px;';
                images.forEach(function (url) {
                    var img = document.createElement('img');
                    img.src = url;
                    img.style.cssText = 'width:64px;height:64px;object-fit:cover;border-radius:6px;border:1px solid #e5e7eb;';
                    gallery.appendChild(img);
                });
                qrPhotos.appendChild(gallery);

                var form = document.createElement('form');
                form.method = 'POST';
                form.action = PHOTOS_URL;
                var html = '<input type="hidden" name="_token" value="' + CSRF + '">' +
                    '<input type="hidden" name="product_id" value="' + prod.id + '">';
                images.forEach(function (url) {
                    html += '<input type="hidden" name="images[]" value="' + url.replace(/"/g, '&quot;') + '">';
                });
                form.innerHTML = html;
                var send = document.createElement('button');
                send.type = 'submit';
                send.textContent = 'Enviar ' + images.length + ' foto(s)';
                send.style.cssText = 'font-size:13px;padding:6px 14px;border-radius:6px;border:none;background:#f59e0b;color:#fff;cursor:pointer;';
                form.appendChild(send);
                qrPhotos.appendChild(form);

                qrPhotos.style.display = '';
                qrPhotos.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }

            function crumbBase(prod) {
                var base = prod.nombre;
                if (state.filters.length) {
                    base += ' · ' + state.filters.map(function (f) { return f.value; }).join(' · ');
                }
                return base;
            }

            function render() {
                qrButtons.innerHTML = '';
                clearPhotos();

                if (state.level === 0) {
                    qrBack.style.display = 'none';
                    qrCrumb.textContent = 'Elige una categoría.';
                    MENU.forEach(function (g, i) {
                        qrButtons.appendChild(pill(g.categoria + ' (' + g.products.length + ')', {
                            accent: '#eef2ff', color: '#3730a3',
                            onClick: function () { state.level = 1; state.cat = i; render(); }
                        }));
                    });
                    return;
                }

                if (state.level === 1) {
                    qrBack.style.display = '';
                    var g = MENU[state.cat];
                    qrCrumb.textContent = g.categoria + ' · elige un producto.';
                    g.products.forEach(function (p, j) {
                        qrButtons.appendChild(pill(p.nombre, {
                            onClick: function () { state.level = 2; state.prod = j; state.filters = []; state.pendingTag = null; render(); }
                        }));
                    });
                    return;
                }

                // level 2 — the tag-narrowing drill
                qrBack.style.display = '';
                var prod = curProduct();
                var variants = inScope(prod);

                // Sub-view: offering the values of a multi-value tag.
                if (state.pendingTag) {
                    var tag = state.pendingTag;
                    qrCrumb.textContent = crumbBase(prod) + ' · elige ' + prettyLabel(tag);
                    distinctValues(variants, tag).forEach(function (val) {
                        qrButtons.appendChild(pill(val, {
                            accent: '#f5f3ff', color: '#5b21b6',
                            onClick: function () { state.filters.push({ tag: tag, value: val }); state.pendingTag = null; render(); }
                        }));
                    });
                    return;
                }

                qrCrumb.textContent = crumbBase(prod) + ' · elige qué enviar o acota.';

                // Row 1: product-level tags (only at the top of the drill). Terminal.
                if (state.filters.length === 0) {
                    prod.product_tags.forEach(function (t) {
                        qrButtons.appendChild(pill(t.label, {
                            accent: '#eef2ff', color: '#3730a3',
                            onClick: function () { fillReply(t.text); }
                        }));
                    });
                }

                // Scanned variant tags: one value → terminal; many → narrow deeper.
                scanTags(variants).forEach(function (tag) {
                    var vals = distinctValues(variants, tag);
                    if (vals.length === 0) return;
                    if (vals.length === 1) {
                        var text = prettyLabel(tag) + ' de ' + prod.nombre + ': ' + vals[0];
                        qrButtons.appendChild(pill(prettyLabel(tag), {
                            accent: '#f5f3ff', color: '#5b21b6',
                            onClick: function () { fillReply(text); }
                        }));
                    } else {
                        qrButtons.appendChild(pill(prettyLabel(tag) + ' ▸', {
                            onClick: function () { state.pendingTag = tag; render(); }
                        }));
                    }
                });

                // Medidas (scope-aware, terminal).
                if (hasMedidas(variants)) {
                    var mt = medidasText(prod, variants);
                    if (mt) {
                        qrButtons.appendChild(pill('📏 Medidas', {
                            accent: '#eff6ff', color: '#1e40af',
                            onClick: function () { fillReply(mt); }
                        }));
                    }
                }

                // Precio (scope-aware, terminal).
                var pt = priceText(prod, variants);
                if (pt) {
                    qrButtons.appendChild(pill('💰 Precio', {
                        accent: '#ecfdf5', color: '#065f46',
                        onClick: function () { fillReply(pt); }
                    }));
                }

                // Fotos (scope-aware).
                var imgs = scopeImages(prod, variants);
                if (imgs.length) {
                    qrButtons.appendChild(pill('📷 Fotos', {
                        accent: '#fef3c7', color: '#92400e',
                        onClick: function () { showPhotos(prod, imgs); }
                    }));
                }
            }

            qrBack.addEventListener('click', function () {
                if (state.level === 2) {
                    if (state.pendingTag) { state.pendingTag = null; render(); return; }
                    if (state.filters.length) { state.filters.pop(); render(); return; }
                    state.level = 1; render(); return;
                }
                if (state.level === 1) { state.level = 0; render(); return; }
            });

            render();
        }
    })();
    </script>
</x-app-layout>
