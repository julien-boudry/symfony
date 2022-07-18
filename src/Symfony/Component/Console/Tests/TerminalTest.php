<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Console\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\AnsiColor;
use Symfony\Component\Console\Terminal;

class TerminalTest extends TestCase
{
    private $colSize;
    private $lineSize;
    private $ansiCon;

    protected function setUp(): void
    {
        $this->colSize = getenv('COLUMNS');
        $this->lineSize = getenv('LINES');
        $this->ansiCon = getenv('ANSICON');
        $this->resetStatics();
    }

    protected function tearDown(): void
    {
        putenv($this->colSize ? 'COLUMNS='.$this->colSize : 'COLUMNS');
        putenv($this->lineSize ? 'LINES' : 'LINES='.$this->lineSize);
        putenv($this->ansiCon ? 'ANSICON='.$this->ansiCon : 'ANSICON');
        $this->resetStatics();
    }

    private function resetStatics()
    {
        foreach (['height', 'width', 'stty'] as $name) {
            $property = new \ReflectionProperty(Terminal::class, $name);
            $property->setValue(null);
        }
    }

    public function test()
    {
        putenv('COLUMNS=100');
        putenv('LINES=50');
        $terminal = new Terminal();
        $this->assertSame(100, $terminal->getWidth());
        $this->assertSame(50, $terminal->getHeight());

        putenv('COLUMNS=120');
        putenv('LINES=60');
        $terminal = new Terminal();
        $this->assertSame(120, $terminal->getWidth());
        $this->assertSame(60, $terminal->getHeight());
    }

    public function testZeroValues()
    {
        putenv('COLUMNS=0');
        putenv('LINES=0');

        $terminal = new Terminal();

        $this->assertSame(0, $terminal->getWidth());
        $this->assertSame(0, $terminal->getHeight());
    }

    public function testSttyOnWindows()
    {
        if ('\\' !== \DIRECTORY_SEPARATOR) {
            $this->markTestSkipped('Must be on windows');
        }

        $sttyString = exec('(stty -a | grep columns) 2>&1', $output, $exitcode);
        if (0 !== $exitcode) {
            $this->markTestSkipped('Must have stty support');
        }

        $matches = [];
        if (0 === preg_match('/columns.(\d+)/i', $sttyString, $matches)) {
            $this->fail('Could not determine existing stty columns');
        }

        putenv('COLUMNS');
        putenv('LINES');
        putenv('ANSICON');

        $terminal = new Terminal();
        $this->assertSame((int) $matches[1], $terminal->getWidth());
    }

    public function testGetTermColorSupport()
    {
        $colorterm = getenv('COLORTERM');
        $term = getenv('TERM');

        try {
            putenv('COLORTERM=truecolor');
            putenv('TERM');
            $this->assertSame(AnsiColor::Ansi24, Terminal::getTermColorSupport());

            putenv('COLORTERM=TRUECOLOR');
            putenv('TERM');
            $this->assertSame(AnsiColor::Ansi24, Terminal::getTermColorSupport());

            putenv('COLORTERM=somethingLike256Color');
            putenv('TERM');
            $this->assertSame(AnsiColor::Ansi8, Terminal::getTermColorSupport());

            putenv('COLORTERM');
            putenv('TERM=xterm-truecolor');
            $this->assertSame(AnsiColor::Ansi24, Terminal::getTermColorSupport());

            putenv('COLORTERM');
            putenv('TERM=xterm-TRUECOLOR');
            $this->assertSame(AnsiColor::Ansi24, Terminal::getTermColorSupport());

            putenv('COLORTERM');
            putenv('TERM=xterm-256color');
            $this->assertSame(AnsiColor::Ansi8, Terminal::getTermColorSupport());

            putenv('COLORTERM');
            putenv('TERM=xterm-256COLOR');
            $this->assertSame(AnsiColor::Ansi8, Terminal::getTermColorSupport());

            putenv('COLORTERM');
            putenv('TERM');
            $this->assertSame(AnsiColor::Ansi4, Terminal::getTermColorSupport());
        } finally {
            (false !== $colorterm) ? putenv('COLORTERM='.$colorterm) : putenv('COLORTERM');
            (false !== $term) ? putenv('TERM='.$term) : putenv('TERM');
        }
    }
}
