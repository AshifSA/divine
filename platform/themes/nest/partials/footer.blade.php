@php
    Theme::asset()->container('footer')->usePath()->add('ai-tryon-js', 'js/ai-tryon.js', ['jquery', 'bootstrap-js']);
@endphp

<footer class="main">
    {!! dynamic_sidebar('pre_footer_sidebar') !!}

    @if($footerSidebar = dynamic_sidebar('footer_sidebar'))
        <section class="section-padding footer-mid">
            <div class="container pt-15 pb-20">
                <div class="row">
                    {!! $footerSidebar !!}
                </div>
            </div>
        </section>
    @endif
    <div class="container pb-30  wow animate__animated animate__fadeInUp"  data-wow-delay="0">
        <div class="row align-items-center">
            <div class="col-12 mb-30">
                <div class="footer-bottom"></div>
            </div>
            @if($copyright = Theme::getSiteCopyright())
                <div class="col-xl-4 col-lg-6 col-md-6">
                    <p class="font-sm mb-0">{!! BaseHelper::clean($copyright) !!}</p>
                </div>
            @endif
            @if ($hotline = theme_option('hotline'))
                <div class="col-xl-4 col-lg-6 text-center d-none d-xl-block">
                    <div class="hotline d-lg-inline-flex w-full align-items-center justify-content-center">
                        <img src="{{ Theme::asset()->url('imgs/theme/icons/phone-call.svg') }}" alt="hotline" />
                        <p>{{ $hotline }} <span>{{ theme_option('hotline_subtitle_text') ?: __('24/7 Support Center') }}</span></p>
                    </div>
                </div>
            @endif
            @if ($socialLinks = theme_option('social_links'))
                @if($socialLinks = json_decode($socialLinks, true))
                    <div @class(['col-lg-6 text-end d-none d-md-block', 'col-xl-4' => $hotline, 'col-xl-8' => ! $hotline])>
                        <div class="mobile-social-icon">
                            <p class="font-heading h6 me-2">{{ __('Follow Us') }}</p>
                            @foreach($socialLinks as $socialLink)
                                @if (count($socialLink) == 3)
                                    <a href="{{ $socialLink[2]['value'] }}"
                                       title="{{ $socialLink[0]['value'] }}">
                                        {{ RvMedia::image($socialLink[1]['value'], $socialLink[0]['value']) }}
                                    </a>
                                @endif
                            @endforeach
                        </div>
                        <p class="font-sm">{{ theme_option('subscribe_social_description', __('Up to 15% discount on your first subscribe')) }}</p>
                    </div>
                @endif
            @endif
        </div>
    </div>
</footer>

<div class="modal fade custom-modal" id="quick-view-modal" tabindex="-1" aria-labelledby="quick-view-modal-label" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            <div class="modal-body">
                <div class="half-circle-spinner loading-spinner">
                    <div class="circle circle-1"></div>
                    <div class="circle circle-2"></div>
                </div>
                <div class="quick-view-content"></div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade custom-modal" id="ai-tryon-modal" tabindex="-1" aria-labelledby="ai-tryon-modal-label" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            <div class="modal-body">
                <h5 class="mb-3" id="ai-tryon-modal-label">{{ __('AI Saree Try-On') }}</h5>

                <div class="mb-3">
                    <small class="text-muted">
                        {{ __('Upload your own photo (or a photo you have permission to use). Results may vary; face-preservation is best-effort.') }}
                    </small>
                </div>

                <form id="ai-tryon-form" class="mb-4" enctype="multipart/form-data">
                    <div class="row g-3">
                        <div class="col-12 col-lg-6">
                            <label class="form-label">{{ __('Your photo') }}</label>
                            <input class="form-control" type="file" name="photo" accept="image/*" required>
                        </div>
                        <div class="col-12 col-lg-6">
                            <label class="form-label">{{ __('Prompt (optional)') }}</label>
                            <textarea class="form-control" name="prompt" rows="4" placeholder="{{ __('Leave blank to use the default try-on prompt') }}"></textarea>
                        </div>
                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="1" id="ai-tryon-consent" name="consent" required>
                                <label class="form-check-label" for="ai-tryon-consent">
                                    {{ __('I confirm I have permission to use this photo.') }}
                                </label>
                            </div>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary" id="ai-tryon-submit">
                                {{ __('Generate') }}
                            </button>
                        </div>
                    </div>
                </form>

                <div class="ai-tryon-status mb-3 d-none"></div>
                <div class="ai-tryon-results row g-3"></div>
            </div>
        </div>
    </div>
</div>
