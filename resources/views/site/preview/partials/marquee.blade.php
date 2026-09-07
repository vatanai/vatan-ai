@php($models = ['GPT Image', 'Flux', 'Kling', 'Veo', 'Seedance', 'Nano Banana', 'Imagen', 'Ideogram', 'Sora', 'Luma', 'Runway', 'Wan'])
<section class="vp-models" aria-label="مدل‌ها و موتورهای هوش مصنوعی پشتیبانی‌شده">
    @foreach ([false, true] as $reverse)
        <div class="vp-models__row {{ $reverse ? 'vp-models__row--reverse' : '' }}">
            <div class="vp-models__track">
                @foreach (array_merge($models, $models) as $model)
                    <span class="vp-model-name">{{ $model }}<i aria-hidden="true"></i></span>
                @endforeach
            </div>
        </div>
    @endforeach
</section>
