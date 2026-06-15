(function() {
  document.querySelectorAll('.wm-product-gallery').forEach(function(gallery) {
    var mainLink = gallery.querySelector('.wm-product-gallery__image');
    var mainImage = gallery.querySelector('.wm-product-gallery__image img');
    var zoomLink = gallery.querySelector('.wm-product-gallery__zoom');
    var thumbs = gallery.querySelectorAll('.wm-product-gallery__thumb');

    if (!mainImage || !thumbs.length) {
      return;
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

        if (mainLink) {
          mainLink.href = full;
        }
        if (zoomLink) {
          zoomLink.href = full;
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
