<?php
/**
 * Created by IntelliJ IDEA.
 * User: stopka
 * Date: 9.12.17
 * Time: 13:33
 */

namespace Stopka\NetteFormRenderer\Forms;


use Stopka\NetteFormRenderer\Forms\Controls\StandardizedCheckbox;

trait TContainerStandardizedCheckboxControl
{
    /**
     * Adds check box control to the form.
     * @param string
     * @param string|object
     * @return Controls\StandardizedCheckbox
     */
    public function addStandardizedCheckbox(string $name, ?string $caption = null): StandardizedCheckbox
    {
        return $this[$name] = new Controls\StandardizedCheckbox($caption);
    }
}
