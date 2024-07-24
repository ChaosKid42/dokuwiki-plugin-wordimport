<?php

namespace dokuwiki\plugin\wordimport\docx;

class Image extends Paragraph
{

    protected $rId = '';
    protected $alt = '';

    public function parse()
    {
        parent::parse();

        $blip = $this->p->xpath('w:r/w:drawing/wp:inline//a:blip')[0];
        $this->rId = (string) $blip->attributes('r', true)->embed;

        $alt = $this->p->xpath('w:r/w:drawing/wp:inline/wp:docPr')[0];
        $this->alt = $this->clean((string)$alt['descr']);
    }

    public function __toString(): string
    {
        try {
            $src = $this->docx->getRelationships()->getTarget('image', $this->rId);
            $src = $this->docx->getFilePath($src);
        } catch (\Exception $e) {
            return ''; // we don't have an image for this. Ignore
        }

        [$ext, $mime] = mimetype($src);
        if (!$mime) return ''; // not a supported image
        if (!str_starts_with($mime, 'image/')) return ''; // not an image

        $pageid = $this->docx->getPageId();
        if ($pageid) {
            $target = $pageid . '-' . $this->rId . '.' . $ext;
            $this->copyImage($src, $target);
        } else {
            $target = $this->rId . '.' . $ext;
        }
        $target = cleanID($target);

        $target = $this->alignmentPadding($target);
        return '{{' . $target . '|' . $this->alt . '}}';
    }

    /**
     * Copy an image to the media folder
     *
     * Will do nothing if the image already exists and is the same
     *
     * @param string $src The full path to the source image
     * @param string $target The target media id
     * @throws \Exception when the media could not be saved
     */
    protected function copyImage($src, $target)
    {
        if(file_exists(mediaFN($target)) && md5_file($src) === md5_file(mediaFN($target))) {
            // image exists and is the same
            return;
        }

        $auth = auth_quickaclcheck(getNS($target) . ':*');
        $res = media_save(['name' => $src], $target, true, $auth, 'copy');
        if (is_array($res)) {
            throw new \Exception('Failed to save media: ' . $res[0]);
        }
    }

    /**
     * Remove any character we can't allow inside an image tag
     * @param string $string
     * @return string
     */
    protected function clean($string): string
    {
        return str_replace(["\n", '{', '}', '|'], ' ', $string);
    }
}
