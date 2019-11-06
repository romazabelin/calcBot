<?php

namespace App\Conversations;

use Illuminate\Foundation\Inspiring;
use BotMan\BotMan\Messages\Incoming\Answer;
use BotMan\BotMan\Messages\Outgoing\Question;
use BotMan\BotMan\Messages\Outgoing\Actions\Button;
use BotMan\BotMan\Messages\Conversations\Conversation;
use Illuminate\Support\Facades\Validator;

class CalculatorConversation extends Conversation
{
    public function askFirstNumber()
    {
        $this->ask('Enter first number', function(Answer $answer) {
            $data = [
                'first_number' => $answer->getText()
            ];

            $validator = Validator::make($data, [
                'first_number' => 'numeric'
            ]);

            if ($validator->fails()) {
                return $this->repeat('It is not numeric!');
            }

            $this->bot->userStorage()->save($data);
            $this->askSecondNumber();
        });
    }

    public function askSecondNumber()
    {
        $this->ask('Enter second number', function(Answer $answer) {
            $data = [
                'second_number' => $answer->getText()
            ];

            $validator = Validator::make($data, [
                'second_number' => 'numeric'
            ]);

            if ($validator->fails()) {
                return $this->repeat('It is not numeric!');
            }

            $this->bot->userStorage()->save($data);
            $this->askOperation();
        });
    }

    public function askOperation()
    {
        $question = Question::create('Choose operation')
            ->fallback('Error')
            ->callbackId('ask_operation')
            ->addButtons([
                Button::create('+')->value('plus'),
                Button::create('-')->value('minus'),
                Button::create('*')->value('multiplication'),
                Button::create('/')->value('division'),
            ]);

        return $this->ask($question, function(Answer $answer) {
           if($answer->isInteractiveMessageReply()) {
               $data         = $this->bot->userStorage()->find();
               $firstNumber  = $data->get('first_number');
               $secondNumber = $data->get('second_number');

               switch($answer->getValue()) {
                   case 'plus':
                       $res = $firstNumber + $secondNumber;
                       break;
                   case 'minus':
                       $res = $firstNumber - $secondNumber;
                       break;
                   case 'multiplication':
                       $res = $firstNumber * $secondNumber;
                       break;
                   case 'division':
                       $res = $firstNumber / $secondNumber;
                       break;
               }

               $this->say('Result: ' . $res);
               $this->bot->userStorage()->save([
                   'operation_result' => $res
               ]);
               $this->askNextStep();
           }
        });
    }

    public function askNextStep()
    {
        $question = Question::create('What next?')
            ->fallback('Error')
            ->callbackId('what_next')
            ->addButtons([
                Button::create('Continue with current result')->value('continue'),
                Button::create('Start from begin')->value('start')
            ]);

        return $this->ask($question, function(Answer $answer) {
            if ($answer->getValue() == 'start') {
                $this->askFirstNumber();
            } else {
                $data = $this->bot->userStorage()->find();
                $this->bot->userStorage()->save([
                    'first_number' => $data->get('operation_result'),
                ]);
                $this->askSecondNumber();
            }
        });
    }

    /**
     * Start the conversation.
     *
     * @return mixed
     */
    public function run()
    {
        $this->askFirstNumber();
    }
}
