<?php

namespace dokuwiki\plugin\wordimport\docx;

class TextRun  // this is not a paragraph!
{
    protected $formatting = [
        'bold' => false,
        'italic' => false,
        'underline' => false,
        'strike' => false,
        'mono' => false,
    ];

    protected $text = '';


    public function __construct(\SimpleXMLElement $tr)
    {
        $br = $tr->xpath('w:br');
        if (!empty($br)) {
            $this->text = "\n";  // FIXME this might need to be a forced line break (unless used in CodeBlock)
            return;
        }


        $this->parseFormatting($tr);
        $this->text = $tr->xpath('w:t')[0];
    }

    public function __toString()
    {
        return $this->text;
    }

    public function getFormatting()
    {
        return $this->formatting;
    }

    public function isWhiteSpace()
    {
        return ctype_space($this->text);
    }

    /**
     * @see http://www.datypic.com/sc/ooxml/e-w_rPr-4.html
     * @param \SimpleXMLElement $textRun
     */
    public function parseFormatting(\SimpleXMLElement $textRun)
    {
        $xml = $textRun->asXML();

        $result = $textRun->xpath('w:rPr');
        if (empty($result)) return;

        $r = $result[0]->asXML();

        foreach ($result[0]->children('w', true) as $child) {
            switch ($child->getName()) {
                case 'b':
                case 'bCs':
                    $this->formatting['bold'] = true;
                    break;
                case 'i':
                case 'iCs':
                case 'em':
                    $this->formatting['italic'] = true;
                    break;
                case 'u':
                    $this->formatting['underline'] = true;
                    break;
                case 'strike':
                case 'dstrike':
                    $this->formatting['strike'] = true;
                    break;
                case 'rFonts':
                    if (in_array($child->attributes('w', true)->ascii, ['Courier New', 'Consolas'])) { // fixme make configurable
                        $this->formatting['mono'] = true;
                    }
                    break;
            }
        }
    }
}
