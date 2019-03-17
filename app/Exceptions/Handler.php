<?php

namespace App\Exceptions;

use App\Notifications\ExceptionOccurred;
use Exception;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Notifications\Notifiable;

class Handler extends ExceptionHandler
{
    use Notifiable;

    /**
     * A list of the exception types that should not be reported.
     *
     * @var array
     */
    protected $dontReport = [
        \Illuminate\Auth\AuthenticationException::class,
        \Illuminate\Auth\Access\AuthorizationException::class,
        \Symfony\Component\HttpKernel\Exception\HttpException::class,
        \Illuminate\Database\Eloquent\ModelNotFoundException::class,
        \Illuminate\Session\TokenMismatchException::class,
        \Illuminate\Validation\ValidationException::class,
    ];

    /**
     * Report or log an exception.
     *
     * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
     *
     * @param  \Exception  $exception
     * @return void
     */
    public function report(Exception $exception)
    {
        if (app()->environment('production') && $this->shouldReport($exception)) {
            $this->notify(new ExceptionOccurred($exception));
        }

        parent::report($exception);
    }

    public function routeNotificationForSlack()
    {
        return config('services.slack.endpoint');
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Exception  $exception
     * @return \Illuminate\Http\Response
     */
    public function render($request, Exception $exception)
    {
        if (app()->environment('production')) {
            $statusCode = 400;
            $title = trans('messages.error.title');
            $description = trans('messages.error.description');

            if ($exception instanceof \Illuminate\Database\Eloquent\ModelNotFoundException
                or $exception instanceof \Symfony\Component\HttpKernel\Exception\ NotFoundHttpException) {
                $statusCode = 404;
                $description = $exception->getMessage() ?: trans('messages.error.not_found');
            }

            return response(view('errors.notice', [
                'title' => $title,
                'description' => $description,
            ]), $statusCode);
        }

        return parent::render($request, $exception);
    }

    /**
     * Convert an authentication exception into an unauthenticated response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Auth\AuthenticationException  $exception
     * @return \Illuminate\Http\Response
     */
    protected function unauthenticated($request, AuthenticationException $exception)
    {
        if ($request->expectsJson()) {
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }

        return redirect()->guest(route('sessions.create'));
    }
}
