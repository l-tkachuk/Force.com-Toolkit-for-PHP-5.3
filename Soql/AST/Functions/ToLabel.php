<?php
namespace Codemitte\ForceToolkit\Soql\AST\Functions;

class ToLabel extends SoqlFunction
{
    protected $name = 'toLabel';

    /**
     * @return int: Bitmask calculated out of one or more of
     *              the CONTEXT_* interface constants.
     */
    public function getAllowedContext()
    {
        return self::CONTEXT_SELECT | self::CONTEXT_WHERE; // WHERE: CURRENCY/DEVISION PICKLISTS EXCLUDED!;
    }

    /**
     * Returns an array (one entry for each argument) containing
     * a list of class names that are allowed as an argument
     * for the particular function.
     *
     * @return array<array<string>>
     */
    public function getAllowedArguments()
    {
        return array(
            array('Codemitte\ForceToolkit\Soql\AST\SoqlName')
        );
    }
}
