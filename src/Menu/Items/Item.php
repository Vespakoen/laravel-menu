<?php
namespace Menu\Items;

use HtmlObject\Element;
use HtmlObject\Traits\Tag;
use Menu\Menu;
use Menu\Traits\Content;
use Menu\Traits\MenuObject;

/**
 * An Item in a list
 */
class Item extends MenuObject
{
  /**
   * Create a new item instance
   *
   * @param ItemList $list     The parent
   * @param Tag      $value    The content
   * @param array    $children Facultative children ItemLists
   * @param array    $element  The Item element
   */
  public function __construct(ItemList $list, Tag $value, $children = array(), $element = null)
  {
    $this->parent   = $list;
    $this->children = $children;
    $this->options  = $list->getOption();

    if (!$element) $element = $this->getOption('item.element');
    $this->setElement($element);

    // Create content
    $this->value = $value->setParent($this);
  }

  /**
   * Break off a chain
   *
   * @return ItemList
   */
  public function stop()
  {
    return $this->getParent();
  }

  /**
   * Get the Item's content
   *
   * @return Content
   */
  public function getContent()
  {
    return $this->value;
  }

  /**
   * Render the item
   *
   * @param array
   *
   * @return string
   */
  public function render($depth = 0)
  {
    // Add the active classes
    $value = $this->value->render();
    $this->addActiveClasses();

    // Render children if any
    if ($this->hasChildren()) {
      $value .= $this->children->render($depth + 1);
    }

    // Facultatively render an element around the item
    $element = $this->element;
    if ($element) $value = Element::create($element, $value, $this->attributes)->render();

    return html_entity_decode($value, ENT_QUOTES, 'UTF-8');
  }

  ////////////////////////////////////////////////////////////////////
  ///////////////////////// PUBLIC INTERFACE /////////////////////////
  ////////////////////////////////////////////////////////////////////

  /**
   * Check if this item is active
   *
   * @return boolean
   */
  public function isActive()
  {
    return
      trim($this->getUrl(), '/') == trim($this->getRequest()->getPathInfo(), '/') or
      $this->getUrl() == $this->getRequest()->fullUrl() or
      $this->getUrl() == $this->getRequest()->url();
  }

  /**
   * Check if this item has an active child
   *
   * @return boolean
   */
  public function hasActiveChild()
  {
    if (!$this->hasChildren()) {
      return false;
    }

    foreach ($this->children->getChildren() as $child) {
      if ($child->isActive()) {
        return true;
      }

      if ($child->hasChildren()) {
        return $child->hasActiveChild();
      }
    }

    return false;
  }

  /**
   * Get the url of the Item's content
   *
   * @return string
   */
  protected function getUrl()
  {
    return $this->value->isLink() ? $this->value->getEvaluatedUrl() : null;
  }

  ////////////////////////////////////////////////////////////////////
  ////////////////////////////// HELPERS /////////////////////////////
  ////////////////////////////////////////////////////////////////////

  /**
   * Add the various active classes to an array of attributes
   */
  private function addActiveClasses()
  {
    if ($this->isActive()) {
      $this->addClass($this->getOption('item.active_class'));
    }

    if ($this->hasActiveChild()) {
      $this->addClass($this->getOption('item.active_child_class'));
    }
  }

  ////////////////////////////////////////////////////////////////////
  //////////////////////////// DEPENDENCIES //////////////////////////
  ////////////////////////////////////////////////////////////////////

  /**
   * Get the Request instance
   *
   * @return Request
   */
  public function getRequest()
  {
    return Menu::getContainer('request');
  }

}
