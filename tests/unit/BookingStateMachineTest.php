<?php

namespace Tests\Unit;

use App\Services\BookingStateMachine;
use CodeIgniter\Test\CIUnitTestCase;
use InvalidArgumentException;

class BookingStateMachineTest extends CIUnitTestCase
{
    private BookingStateMachine $machine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->machine = new BookingStateMachine();
    }

    public function testPaymentAndCheckInFlowIsAllowed(): void
    {
        $this->assertTrue($this->machine->canTransition('hold', 'paid'));
        $this->assertTrue($this->machine->canTransition('paid', 'checked_in'));
        $this->assertTrue($this->machine->canTransition('checked_in', 'completed'));
    }

    public function testTerminalBookingCannotBeReopened(): void
    {
        $this->assertFalse($this->machine->canTransition('completed', 'paid'));
        $this->assertFalse($this->machine->canTransition('refunded', 'checked_in'));
    }

    public function testAssertTransitionFailsFast(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->machine->assertTransition('cancelled', 'reserved');
    }
}
