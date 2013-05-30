<?php
namespace Menu\Items;

use HtmlObject\Element;
use Menu\Items\Contents\Link;
use Menu\Items\Contents\Raw;
use Menu\Menu;
use Menu\Traits\MenuObject;

/**
 * A container for Items
 */
class ItemList extends MenuObject
{
  /**
   * The name of this ItemList
   *
   * @var string
   */
  public $name;

  /**
   * Create a new Item List instance
   *
   * @param string  $name        The ItemList's name
   * @param array   $attributes  Attributes for the ItemList's HMTL element
   * @param string  $element     The HTML element for the ItemList
   *
   * @return void
   */
  public function __construct($name = null, $attributes = array(), $element = null)
  {
    if (!$element) $element = $this->getOption('item_list.element');

    $this->name       = $name;
    $this->attributes = $attributes;
    $this->setElement($element);
  }

  /**
   * Get the last Item
   *
   * @return Item
   */
  public function onItem()
  {
    return $this->children[sizeof($this->children) - 1];
  }

  ////////////////////////////////////////////////////////////////////
  ///////////////////////// PUBLIC INTERFACE /////////////////////////
  ////////////////////////////////////////////////////////////////////

  /**
   * Add a link item to the ItemList instance.
   *
   * <code>
   *    // Add a item to the default menu
   *    Menu::add('home', 'Homepage');
   *
   *    // Add a item with a subitem to the default menu
   *    Menu::add('home', 'Homepage', Menu::items()->add('home/sub', 'Subitem'));
   *
   *    // Add a item with attributes for the item's HTML element
   *    Menu::add('home', 'Homepage', null, array('class' => 'fancy'));
   * </code>
   *
   * @param string   $url
   * @param string   $value
   * @param ItemList $children
   * @param array    $linkAttributes
   * @param array    $itemAttributes
   * @param string   $itemElement
   *
   * @return MenuItems
   */
  public function add($url, $value, $children = null, $linkAttributes = array(), $itemAttributes = array(), $itemElement = null)
  {
    $content = new Link($url, $value, $linkAttributes);
    $item = $this->addContent($content, $children, $itemAttributes, $itemElement);

    return $this;
  }

  /**
   * Add an active pattern to the ItemList instance.
   *
   * <code>
   *    // Add a item to the default menu and set an active class for /user/5/edit
   *    Menu::add('user', 'Users')->activePattern('\/user\/\d\/edit');
   * </code>
   *
   * @param string   $pattern
   *
   * @return MenuItems
   */
  public function activePattern( $pattern )
  {
    $pattern = (array) $pattern;
    $item = end($this->children);
    $item->setActivePatterns( $pattern );
    return $this;
  }

  /**
   * Add a raw html item to the MenuItems instance.
   *
   * <code>
   *    // Add a raw item to the default main menu
   *    Menu::raw('<img src="img/seperator.gif">');
   * </code>
   *
   * @param string $raw            The raw content
   * @param array $children        Possible children
   * @param array  $itemAttributes The item attributes
   * @param string $itemElement    The item element
   *
   * @return ItemList
   */
  public function raw($raw, $children = null, $itemAttributes = array(), $itemElement = null)
  {
    $content = new Raw($raw);
    $item = $this->addContent($content, $children, $itemAttributes, $itemElement);

    return $this;
  }

  /**
   * Add a link to the ItemList
   *
   * @param Content $content
   * @param array   $children
   * @param array   $itemAttributes
   * @param string  $itemElement
   */
  public function addContent($content, $children, $itemAttributes, $itemElement)
  {
    $item = new Item($this, $content, $children, $itemElement);
    $item->setAttributes($itemAttributes);

    // Set Item as parent of its children
    if (!is_null($children)) {
      $children->setParent($item);
    }

    $this->setChild($item);

    return $item;
  }

  /**
   * Add menu items to another ItemList.
   *
   * <code>
   *    // Attach menu items to the default menuhandler
   *    Menu::attach(Menu::items()->add('home', 'Homepage'));
   * </code>
   *
   * @param  ItemList $itemList
   *
   * @return ItemList
   */
  public function attach(ItemList $itemList)
  {
    $this->nestChildren($itemList->getChildren());

    return $this;
  }

  /**
   * Set the name for this ItemList
   *
   * @param string  $name
   *
   * @return ItemList
   */
  public function name($name)
  {
    $this->name = $name;

    return $this;
  }

  ////////////////////////////////////////////////////////////////////
  ///////////////////////////// PREFIXES /////////////////////////////
  ////////////////////////////////////////////////////////////////////

  /**
   * Prefix this ItemList with a string
   *
   * @param string $prefix
   *
   * @return ItemList
   */
  public function prefix($prefix)
  {
    $this->setOption('item_list.prefix', $prefix);

    return $this;
  }

  /**
   * Prefix this ItemList with the parent ItemList(s) name(s)
   *
   * @param boolean $prefixParents
   *
   * @return ItemList
   */
  public function prefixParents($prefixParents = true)
  {
    $this->setOption('item_list.prefix_parents', $prefixParents);

    return $this;
  }

  /**
   * Prefix this ItemList with the name of the ItemList at the very top of the tree
   *
   * @param boolean $prefixHandler
   *
   * @return ItemList
   */
  public function prefixHandler($prefixHandler = true)
  {
    $this->setOption('item_list.prefix_handler', $prefixHandler);

    return $this;
  }

  /**
   * Get the evaluated string content of the ItemList.
   *
   * @param  integer $depth The depth at which the ItemList should be rendered
   *
   * @return string
   */
  public function render($depth = 0)
  {
    // Check for maximal depth
    $maxDepth = $this->getOption('max_depth');
    if ($maxDepth != 0 and $depth > $maxDepth) return false;

    // Render contained items
    $contents = null;
    foreach ($this->children as $item) {
      $contents .= $item->render($depth + 1);
    }

    $element = $this->element;
    if ($element) $content = Element::create($element, $contents, $this->attributes)->render();

    return $content;
  }

  /**
   * Find itemslists by name (itself, or on of it's children)
   *
   * @param array $names the names to find
   *
   * @return ItemList
   */
  public function find($names)
  {
    $names = (array) $names;

    $results = array();

    foreach ($names as $name) {
      if ($this->name == $name) {
        $results[] = $this;
      }

      foreach ($this->children as $item) {
        if ($item->hasChildren() && $found = $item->children->find($name)) {
          foreach ($found as $list_item) {
            $results[] = $list_item;
          }
        }
      }
    }

    return $results;
  }
}
