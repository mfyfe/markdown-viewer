<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Exception;

use Michelf\MarkdownExtra;

class MarkdownController extends AbstractController
{
    const VIMWIKI = '/srv/vimwiki/';

    /**
     * @Route("/", name="index")
     * @Route("/{path}", name="path", requirements={"path"=".+"})
     */
    public function number(Request $request)
    {
        $path = $request->get('path') ?: '';

        if (preg_match('/.+\.md$/', $path)) {
            return $this->markdown($path);
        } else {
            if (is_file(self::VIMWIKI . $path)) {
                return $this->anyFile($path);
            }
            return $this->directory($path);
        }
    }

    /**
     * Render Markdown as HTML.
     */
    private function markdown($relativePath)
    {
        $path = self::VIMWIKI . $relativePath;

        if (!file_exists($path)) {
            throw new Exception("$path does not exist.");
        }

        $markdown = file_get_contents($path);
        $html = MarkdownExtra::defaultTransform($markdown);

        return $this->render('markdown.html.twig', [
            'path' => $path,
            'html' => $html,
        ]);
    }

    /**
     * Render non-markdown files as plain text.
     */
    private function anyFile($relativePath)
    {
        $path = self::VIMWIKI . $relativePath;

        $response = new Response();
        $response->setContent(file_get_contents($path));
        $response->headers->set('Content-Type', 'text/plain');
        return $response;
    }

    /**
     * Render directory index for a path.
     */
    private function directory($relativePath)
    {
        $path = self::VIMWIKI . $relativePath;

        // Get parent directory.
        $parent = '.';
        if ($relativePath) {
            $parent = dirname($relativePath, 1);
        }

        $dirContents = scandir($path, SCANDIR_SORT_ASCENDING);

        // Sort files and directories.
        $dirs = [];
        $files = [];
        $baseDir = self::VIMWIKI . $relativePath . '/';
        $urlBase = $relativePath ? "$relativePath/" : '';
        foreach ($dirContents as $n) {
            if (in_array($n, ['.', '..'])) {
                continue;
            }

            if (is_dir($baseDir . $n)) {
                $dirs["$urlBase$n"] = $n;
            }
            if (is_file($baseDir . $n)) {
                $files["$urlBase$n"] = $n;
            }
        }

        return $this->render('directory.html.twig', [
            'path' => $path,
            'parent' => dirname($relativePath, 1),
            'dirs' => $dirs,
            'files' => $files,
        ]);
    }
}
