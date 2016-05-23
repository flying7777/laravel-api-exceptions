<?php

namespace Notimatica\Exceptions;

use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;

class LaravelExceptionHandler extends ExceptionHandler
{
    /**
     * A list of the exception types that should not be reported.
     *
     * @var array
     */
    protected $dontReport = [
        AuthorizationException::class,
        HttpException::class,
        ModelNotFoundException::class,
        ValidationException::class,
        UnauthorizedApiException::class,
    ];

    /**
     * Report or log an exception.
     *
     * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
     *
     * @param  \Exception  $e
     * @return void
     */
    public function report(Exception $e)
    {
        if ($e instanceof ApiException) {
            $e = $e->toReport();
        }

        parent::report($e);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Exception  $e
     * @return \Illuminate\Http\Response
     */
    public function render($request, Exception $e)
    {
        switch (true) {
            case $e instanceof ApiException:
                $response = response()->json($e, $e->getCode(), $e->getHeaders());
                break;
            case $e instanceof TokenExpiredException:
                $response = response()->json(['token_expired'], $e->getStatusCode());
                break;
            case $e instanceof TokenInvalidException:
                $response = response()->json(['token_invalid'], $e->getStatusCode());
                break;
            case $e instanceof AuthorizationException:
                $e = new UnauthorizedApiException('', $e);
                $response = response()->json($e, $e->getCode());
                break;
            case $e instanceof ModelNotFoundException:
            case $e instanceof NotFoundHttpException:
                $e = new NotFoundApiException();
                $response = response()->json($e, $e->getCode());
                break;
            default:
                $e = new InternalServerErrorApiException('', $e);
                $response = response()->json($e, $e->getCode());
                break;
        }

        return $response;
    }
}