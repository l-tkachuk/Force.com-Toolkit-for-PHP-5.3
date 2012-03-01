<?php
/**
 * Copyright (C) 2012 code mitte GmbH - Zeughausstr. 28-38 - 50667 Cologne/Germany
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy of
 * this software and associated documentation files (the "Software"), to deal in the
 * Software without restriction, including without limitation the rights to use,
 * copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the
 * Software, and to permit persons to whom the Software is furnished to do so, subject
 * to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED,
 * INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A
 * PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
 * HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
 * OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE
 * SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */

namespace Codemitte\Sfdc\Soql\Type;

/**
 * DateTime
 *
 * @author Johannes Heinen <johannes.heinen@code-mitte.de>
 * @copyright 2012 code mitte GmbH, Cologne, Germany
 * @package Sfdc
 * @subpackage Soql
 */
class DateTime extends Date
{
    /**
     * Date only	YYYY-MM-DD	1999-01-01
     * Date, time, and time zone offset
     * YYYY-MM-DDThh:mm:ss+hh:mm
     * YYYY-MM-DDThh:mm:ss-hh:mm
     * YYYY-MM-DDThh:mm:ssZ
     * 1999-01-01T23:01:01+01:00
     * 1999-01-01T23:01:01-08:00
     *
     * @return string
     */
    public function toSOQL()
    {
        return $this->format('Y-m-d\TH:m:s') . $this->getOffsetString($this->getOffset());
    }

    /**
     * getOffsetString()
     *
     * @param param integer $offset : In seconds (Result of getOffset())
     * @return string
     */
    public function getOffsetString($offset)
    {
        $oAbs = abs($offset);

        $oh = (int)$oAbs / 3600;

        $om = (int)(($oAbs - $oh * 3600) / 60);

        return ($offset < 0 ? '-' : '+') . ($oh > 10 ? '' : '0') . $oh . ':' . ($om > 10 ? '' : '0') . $om;
    }
}