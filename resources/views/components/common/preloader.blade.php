<div
  x-show="loaded"
  x-init="window.addEventListener('DOMContentLoaded', () => {setTimeout(() => loaded = false, 450)})"
  x-transition.opacity.duration.300ms
  class="fixed left-0 top-0 z-999999 flex h-screen w-screen items-center justify-center bg-white dark:bg-gray-950"
>
  <div class="flex flex-col items-center justify-center gap-3 text-center">
    <img
      src="{{ asset('landing-assets/images/LoadingBee.gif') }}"
      alt="Loading ReadBee"
      class="h-24 w-24 object-contain sm:h-50 sm:w-50"
    />
  </div>
</div>
