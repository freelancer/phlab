/**
 * @provides javelin-behavior-differential-hide-lint-inlines
 * @javelin
 */

// No `@requires`: Celerity resolves a phlab resource's requirements in phlab's
// map only, so naming core resources here fails every page that loads this.
// The revision page loads `JX.DiffChangesetList` and `JX.PHUIXActionView`.

/**
 * Adds "Hide Lint Inlines" to the inline comments menu on a revision.
 *
 * This wraps three private methods of core's `JX.DiffChangesetList`
 * (`_getMenuButton`, `_ondropdown`, `_toggleInlines`) and calls three more
 * (`_getInlinesByType`, `_onhideinlines`, `_setSelectionState`). Check them
 * whenever the Phabricator version production runs moves. If any is missing,
 * nothing is patched and the menu is left as core builds it.
 *
 * Core calls lint messages "synthetic" inlines: they are the only inlines
 * Differential synthesizes, so the two words mean the same set here.
 *
 * The item is only added to a menu built after this runs, because the menu's
 * "open" listener is bound to `_ondropdown` when the menu is built. A list
 * builds its menu at load when the page opens scrolled into the diff, and this
 * still runs first because behaviors run in the order PHP initialized them
 * (`CelerityStaticResourceResponse::renderHTMLFooter()`): the curtain, whose
 * listener initializes this, renders before the changeset list
 * (`PHUITwoColumnView::getTagContent()`, and the footer in
 * `DifferentialRevisionViewController`).
 */
JX.behavior('differential-hide-lint-inlines', function(config) {
  var list_class = JX.DiffChangesetList;
  if (!list_class || list_class._installedHideLint) {
    return;
  }

  var proto = list_class.prototype;
  var used = [
    '_getInlinesByType',
    '_getMenuButton',
    '_ondropdown',
    '_onhideinlines',
    '_setSelectionState',
    '_toggleInlines'
  ];
  for (var ii = 0; ii < used.length; ii++) {
    if (typeof proto[used[ii]] !== 'function') {
      return;
    }
  }

  list_class._installedHideLint = true;

  // Core calls `_getInlinesByType()` on every scroll, so it is read here
  // rather than wrapped: if its result changes shape, only this item breaks.
  var getVisibleLint = function(list) {
    var inlines = list._getInlinesByType();
    var visible = (inlines && inlines.visible) || [];

    var lint = [];
    for (var ii = 0; ii < visible.length; ii++) {
      if (visible[ii].isSynthetic()) {
        lint.push(visible[ii]);
      }
    }

    return lint;
  };

  var original_getMenuButton = proto._getMenuButton;
  proto._getMenuButton = function() {
    var is_new = !this._menuButton;
    var button = original_getMenuButton.apply(this, arguments);

    var items = this._menuItems;
    if (is_new && items && items.hideAll) {
      items.hideLint = new JX.PHUIXActionView()
        .setHandler(JX.bind(this, this._onhideinlines, 'lint'));

      var hide_all = items.hideAll.getNode();
      hide_all.parentNode.insertBefore(items.hideLint.getNode(), hide_all);
    }

    return button;
  };

  var original_ondropdown = proto._ondropdown;
  proto._ondropdown = function() {
    original_ondropdown.apply(this, arguments);

    var item = this._menuItems && this._menuItems.hideLint;
    if (item) {
      item
        .setName(config.name)
        .setDisabled(!getVisibleLint(this).length);
    }
  };

  var original_toggleInlines = proto._toggleInlines;
  proto._toggleInlines = function(type) {
    if (type !== 'lint') {
      return original_toggleInlines.apply(this, arguments);
    }

    this._setSelectionState(null);

    var lint = getVisibleLint(this);
    for (var ii = 0; ii < lint.length; ii++) {
      lint[ii].setHidden(true);
    }
  };
});
