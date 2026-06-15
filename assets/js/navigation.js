(function(){
  const nav = document.getElementById('site-navigation');
  if (nav) {
    const button = nav.querySelector('.menu-toggle');
    const menu = nav.querySelector('ul');
    if (button && menu) {
      button.addEventListener('click', function() {
        const expanded = button.getAttribute('aria-expanded') === 'true';
        button.setAttribute('aria-expanded', String(!expanded));
        nav.classList.toggle('is-open', !expanded);
      });
    }
  }

  const searchToggle = document.querySelector('.site-header__search-toggle');
  const searchPanel = document.getElementById('site-header-search');
  if (searchToggle && searchPanel) {
    searchToggle.addEventListener('click', function() {
      const expanded = searchToggle.getAttribute('aria-expanded') === 'true';
      searchToggle.setAttribute('aria-expanded', String(!expanded));
      searchPanel.hidden = expanded;
    });
  }
})();
