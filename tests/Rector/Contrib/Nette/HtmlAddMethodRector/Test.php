<?php declare(strict_types=1);

namespace Rector\Tests\Rector\Contrib\Nette\HtmlAddMethodRector;

use Rector\Rector\Contrib\Nette\HtmlAddMethodRector;
use Rector\Testing\PHPUnit\AbstractRectorTestCase;

final class Test extends AbstractRectorTestCase
{
    public function test(): void
    {
        //        $this->doTestFileMatchesExpectedContent(
        //            __DIR__ . '/wrong/wrong.php.inc',
        //            __DIR__ . '/correct/correct.php.inc'
        //        );
        //        $this->doTestFileMatchesExpectedContent(
        //            __DIR__ . '/wrong/wrong2.php.inc',
        //            __DIR__ . '/correct/correct2.php.inc'
        //        );
        //        $this->doTestFileMatchesExpectedContent(
        //            __DIR__ . '/wrong/wrong3.php.inc',
        //            __DIR__ . '/correct/correct3.php.inc'
        //        );
        //        $this->doTestFileMatchesExpectedContent(
        //            __DIR__ . '/wrong/wrong4.php.inc',
        //            __DIR__ . '/correct/correct4.php.inc'
        //        );
        $this->doTestFileMatchesExpectedContent(
            __DIR__ . '/Wrong/SomeClass.php',
            __DIR__ . '/Correct/SomeClass.php'
        );
    }

    /**
     * @return string[]
     */
    protected function getRectorClasses(): array
    {
        return [HtmlAddMethodRector::class];
    }
}
