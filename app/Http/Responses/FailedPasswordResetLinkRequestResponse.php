<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\FailedPasswordResetLinkRequestResponse as FailedPasswordResetLinkRequestResponseContract;
use Symfony\Component\HttpFoundation\Response;

/**
 * « Mot de passe oublié » answers the same whether the address has an account
 * or not: telling « no account » apart would let anyone test addresses to
 * learn who is a DAG client. A link asked again within the minute (only
 * possible for an existing account) gets the same answer too.
 */
class FailedPasswordResetLinkRequestResponse implements FailedPasswordResetLinkRequestResponseContract
{
    public function __construct(private string $status) {}

    public function toResponse($request): Response
    {
        if (in_array($this->status, [Password::INVALID_USER, Password::RESET_THROTTLED], true)) {
            $message = trans(Password::RESET_LINK_SENT);

            return $request->wantsJson()
                ? new JsonResponse(['message' => $message], 200)
                : back()->with('status', $message);
        }

        if ($request->wantsJson()) {
            throw ValidationException::withMessages(['email' => [trans($this->status)]]);
        }

        return back()
            ->withInput($request->only('email'))
            ->withErrors(['email' => trans($this->status)]);
    }
}
