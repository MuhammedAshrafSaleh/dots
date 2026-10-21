{{-- Motion scaffolding (Motion.jsx): first-visit preloader, page-transition
     curtain, custom cursor. All are progressive enhancement driven by
     resources/js/site/motion.js and disabled under prefers-reduced-motion
     (and when JS is off). Markup only; behaviour lives in the JS. --}}

{{-- Page-transition curtain (ink panel + yellow leading edge). --}}
<div class="d-curtain" data-motion="curtain" aria-hidden="true" hidden>
    <div class="d-curtain__y"></div>
    <div class="d-curtain__k"><span class="d-curtain__label"><span class="d-dot"></span><span data-curtain-label></span></span></div>
</div>

{{-- Custom cursor (desktop fine-pointer only; JS enables it). --}}
<div class="d-cur__ring" data-motion="cursor-ring" aria-hidden="true"><span class="d-cur__disc"><span class="d-cur__label" data-cursor-label></span></span></div>
<div class="d-cur__dot" data-motion="cursor-dot" aria-hidden="true"><span></span></div>

{{-- First-visit preloader. Shown synchronously only when <html> has .d-preload
     (set by the inline head script on the first visit of a session). --}}
<div class="d-pre" data-motion="preloader" aria-hidden="true">
    <div class="d-pre__accent"></div>
    <div class="d-pre__panel">
        <div class="d-pre__in">
            <div class="d-pre__brand" dir="ltr">
                <img src="{{ asset('images/mark.png') }}" alt="" aria-hidden="true">
                <span>Dots</span>
            </div>
            <div class="d-pre__line"><span style="width:0%"><i></i></span></div>
            <div class="d-pre__meta"><span>Lighting · Smart Home · Electrical</span><b data-pre-count>000</b></div>
        </div>
    </div>
</div>
