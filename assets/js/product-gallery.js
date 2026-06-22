(function() {
  var ZOOM_SCALE = 2;
  var lightbox = null;

  function buildLightbox() {
    if (lightbox) {
      return lightbox;
    }

    var overlay = document.createElement('div');
    overlay.className = 'wm-lightbox';
    overlay.setAttribute('role', 'dialog');
    overlay.setAttribute('aria-modal', 'true');
    overlay.innerHTML =
      '<button type="button" class="wm-lightbox__close" aria-label="Close">&times;</button>' +
      '<div class="wm-lightbox__stage"><img class="wm-lightbox__img" src="" alt=""></div>';

    document.body.appendChild(overlay);

    function close() {
      overlay.classList.remove('is-open');
      document.body.classList.remove('wm-lightbox-open');
    }

    overlay.addEventListener('click', function(event) {
      if (event.target === overlay || event.target.closest('.wm-lightbox__close')) {
        close();
      }
    });

    document.addEventListener('keydown', function(event) {
      if (event.key === 'Escape' && overlay.classList.contains('is-open')) {
        close();
      }
    });

    lightbox = {
      open: function(src, alt) {
        var img = overlay.querySelector('.wm-lightbox__img');
        img.src = src;
        img.alt = alt || '';
        overlay.classList.add('is-open');
        document.body.classList.add('wm-lightbox-open');
      },
      close: close
    };

    return lightbox;
  }

  document.querySelectorAll('.wm-product-gallery').forEach(function(gallery) {
    var mainWrap = gallery.querySelector('.wm-product-gallery__image');
    var mainImage = gallery.querySelector('.wm-product-gallery__image img');
    var zoomButton = gallery.querySelector('.wm-product-gallery__zoom');
    var thumbs = gallery.querySelectorAll('.wm-product-gallery__thumb');

    if (!mainImage) {
      return;
    }

    if (mainWrap) {
      mainWrap.addEventListener('mousemove', function(event) {
        var full = mainWrap.getAttribute('data-full');
        if (!full) {
          return;
        }
        var rect = mainWrap.getBoundingClientRect();
        var x = ( ( event.clientX - rect.left ) / rect.width ) * 100;
        var y = ( ( event.clientY - rect.top ) / rect.height ) * 100;
        x = Math.max( 0, Math.min( 100, x ) );
        y = Math.max( 0, Math.min( 100, y ) );

        mainWrap.classList.add('is-zooming');
        mainWrap.style.backgroundImage = 'url(' + full + ')';
        mainWrap.style.backgroundSize = ( ZOOM_SCALE * 100 ) + '%';
        mainWrap.style.backgroundPosition = x + '% ' + y + '%';
      });

      mainWrap.addEventListener('mouseleave', function() {
        mainWrap.classList.remove('is-zooming');
        mainWrap.style.backgroundImage = '';
      });
    }

    if (zoomButton) {
      zoomButton.addEventListener('click', function() {
        var full = zoomButton.getAttribute('data-full');
        var alt = zoomButton.getAttribute('data-alt') || mainImage.alt;
        if (full) {
          buildLightbox().open(full, alt);
        }
      });
    }

    thumbs.forEach(function(thumb) {
      thumb.addEventListener('click', function() {
        var large = thumb.getAttribute('data-large');
        var full = thumb.getAttribute('data-full') || large;
        var srcset = thumb.getAttribute('data-srcset');
        var sizes = thumb.getAttribute('data-sizes');
        var alt = thumb.getAttribute('data-alt') || '';

        if (!large) {
          return;
        }

        mainImage.src = large;
        if (srcset) {
          mainImage.srcset = srcset;
        } else {
          mainImage.removeAttribute('srcset');
        }
        if (sizes) {
          mainImage.sizes = sizes;
        } else {
          mainImage.removeAttribute('sizes');
        }
        mainImage.alt = alt;

        if (mainWrap) {
          mainWrap.setAttribute('data-full', full);
        }
        if (zoomButton) {
          zoomButton.setAttribute('data-full', full);
          zoomButton.setAttribute('data-alt', alt);
        }

        thumbs.forEach(function(item) {
          item.classList.remove('is-active');
          item.setAttribute('aria-pressed', 'false');
        });
        thumb.classList.add('is-active');
        thumb.setAttribute('aria-pressed', 'true');
      });
    });
  });
})();
