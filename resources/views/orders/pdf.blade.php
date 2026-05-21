
<style>
        
    </style>
    <div  >
        <div class="">
            <div >
            
                <div >
            <div>
            <img  style="margin-bottom:20px;" height="30px" src="{{ asset(env('LOGO_ASSET')) }}">
        </div>        
                        
                        <div>
                            Nombre: {{ $nombre }}
                        </div>
                        <div>
                            Telefonos: {{ $telefonos }}
                        </div>
                        <div>
                            Direccion: {{ $direccion }}
                        </div>
                        <div >
                            TOTAL: Q.{{ number_format($total, 2, '.', ',') }}
                        </div>
                        @if ($pagado)
                            <strong >PAGADO</strong>
                        @endif
                        <div >
                        {{ $notas ? 'NOTAS: ' . $notas : '' }}
                        </div>
                        @foreach ($items as $item)
                        <div>
                        {{ $item->cantidad }} x {{ $item->descripcion }} {{ $item->variant->codigo }}
                        </div>
                        @endforeach
                        
                </div>
            </div>
        </div>
    </div>
