<?php
/**
 * Created by IntelliJ IDEA.
 * User: stopka
 * Date: 9.12.17
 * Time: 13:33
 */

namespace Stopka\NetteFormRenderer\Forms;


use Nette\Utils\Html;

trait TContainerHtmlControl {
    /**
     * Adds check box control to the form.
     * @param string
     * @param string|object
     * @param Html $html
     * @return Controls\Html
     */
    public function addHtml($name, $caption = null, Html $html) {
        return $this[$name] = new Controls\Html($caption,  $html);
    }
}