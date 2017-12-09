<?php
/**
 * Created by IntelliJ IDEA.
 * User: stopka
 * Date: 9.3.15
 * Time: 15:47
 */

namespace Stopka\NetteFormRenderer\Forms\Controls;


use Nette\Forms\Controls\BaseControl;
use Nette\Utils\Html as HtmlUtil;

class Html extends BaseControl{
    /**
     * @param  string  label
     * @param HtmlUtil html
     */
    public function __construct($label = NULL, HtmlUtil $html){
        parent::__construct($label);
        $this->setHtml(HtmlUtil::el('div')->addHtml($html));
        $this->setOmitted();
    }

    public function setHtml(HtmlUtil $html){
        $this->control->setName($html->getName());
        $this->control->addHtml($html->getHtml());
        $this->control->addClass("form-html-control");
    }
}