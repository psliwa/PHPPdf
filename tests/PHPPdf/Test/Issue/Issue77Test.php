<?php


namespace PHPPdf\Test\Issue;

use PHPPdf\Core\Facade;
use PHPPdf\PHPUnit\Framework\TestCase;

//https://github.com/psliwa/PHPPdf/issues/77
class Issue77Test extends TestCase
{
    /**
     * @var Facade
     */
    private $facade;

    protected function setUp()
    {
        $loader = new \PHPPdf\Core\Configuration\LoaderImpl();
        $builder = \PHPPdf\Core\FacadeBuilder::create($loader);
        $this->facade = $builder->build();
    }

    /**
     * @test
     * @dataProvider booleanProvider
     */
    public function testFooterRender($useTemplate)
    {
        $this->renderDocumentWithFooter('Document 1', $useTemplate);
        $content = $this->renderDocumentWithFooter('Document 2', $useTemplate);

        $this->assertContains('Document 2', $content);
        $this->assertNotContains('Document 1', $content);
    }

    public function booleanProvider()
    {
        return array(
            array(true),
            array(false),
        );
    }

    private function renderDocumentWithFooter($footer, $useTemplate)
    {
        $template = $useTemplate ? 'document-template="'.__DIR__.'/../../Resources/test.pdf"' : '';
        return $this->facade->render('<pdf>
    <dynamic-page encoding="UTF-8" '.$template.'>
                <placeholders>
                    <header>
                        <div height="50px" width="100%" color="green">
                            Some header
                        </div>
                    </header>
                    <footer>
                        <div height="50px" width="100%" color="green">
                             '.$footer.'
                        </div>
                    </footer>
                </placeholders>

                <p>Lorum ipsum</p>

            </dynamic-page>
        </pdf>',

            '<stylesheet></stylesheet>'
        );
    }
}