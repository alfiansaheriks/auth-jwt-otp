<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Carbon\Carbon;
use Session;

class JWTAuthController extends Controller
{
    // Step 1: Register user with phone number, send OTP to WhatsApp
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone_number' => 'required|string|min:6|unique:users',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors()->toJson(), 400);
        }

        $phone_number = $request->get('phone_number');
        $otp = rand(100000, 999999); // Generate a 6-digit OTP

        // Store the OTP in the database (you can also use cache or another method)
        User::updateOrCreate(
            ['phone_number' => $phone_number],
            ['otp_code' => $otp, 'otp_expires_at' => Carbon::now()->addMinutes(5)] // OTP valid for 5 minutes
        );

        // Send OTP to WhatsApp using Zenziva
        $this->sendOtpToWhatsApp($phone_number, $otp);

        return response()->json(['message' => 'OTP sent to WhatsApp'], 200);
    }


    // Step 2: Verify OTP and store user in the database
    public function verifyOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone_number' => 'required|string|min:6',
            'otp' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors()->toJson(), 400);
        }

        $phone_number = $request->get('phone_number');
        $otp = $request->get('otp');

        // Find the user by phone number
        $user = User::where('phone_number', $phone_number)->first();

        // Check if user exists and OTP is correct and not expired
        if ($user && $user->otp_code == $otp && $user->otp_expires_at > Carbon::now()) {
            // Mark the phone as verified
            $user->phone_verified_at = Carbon::now();
            $user->otp_code = null; // Clear OTP
            $user->save();

            // Generate a JWT token
            $token = JWTAuth::fromUser($user);

            return response()->json(['message' => 'User registered and verified', 'token' => $token], 200);
        }

        return response()->json(['message' => 'Invalid OTP or expired'], 400);
    }

    public function getUser()
    {
        return response()->json(auth()->user());
    }

    public function logout()
    {
        try {
            JWTAuth::invalidate(JWTAuth::getToken());
            return response()->json(['message' => 'User logged out successfully']);
        } catch (Exception $e) {
            return response()->json(['error' => 'Failed to logout, please try again.'], 500);
        }
    }

    // Send OTP to WhatsApp using Zenziva API
    protected function sendOtpToWhatsApp($phoneNumber, $otp)
    {
        $userkey = '121035334eda'; // Your Zenziva userkey
        $passkey = '9e6087bf8befad27e6cd1d66'; // Your Zenziva passkey
        $my_brand = 'Ayo Ojek'; // Customize the brand name
        $url = 'https://console.zenziva.net/waofficial/api/sendWAOfficial/';

        $postFields = [
            'userkey' => $userkey,
            'passkey' => $passkey,
            'to' => $phoneNumber,
            'brand' => $my_brand,
            'otp' => $otp
        ];

        // Initialize cURL
        $curlHandle = curl_init();
        curl_setopt($curlHandle, CURLOPT_URL, $url);
        curl_setopt($curlHandle, CURLOPT_HEADER, 0);
        curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curlHandle, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($curlHandle, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($curlHandle, CURLOPT_TIMEOUT, 30);
        curl_setopt($curlHandle, CURLOPT_POST, 1);
        curl_setopt($curlHandle, CURLOPT_POSTFIELDS, $postFields);

        // Execute the cURL request and close the handle
        $results = json_decode(curl_exec($curlHandle), true);
        curl_close($curlHandle);

        // Check if the OTP was sent successfully
        if (isset($results['status']) && $results['status'] == 'success') {
            return true;
        } else {
            return response()->json(['message' => 'Failed to send OTP'], 500);
        }
    }

    public function login(Request $request)
    {
        // Include only phone_number in the credentials array
        $credentials = $request->only('phone_number');

        try {
            // Custom authentication logic since password is not used
            $user = User::where('phone_number', $credentials['phone_number'])->first();

            if (!$user) {
                return response()->json(['error' => 'Invalid credentials'], 401);
            }

            // Generate token for the user
            $token = JWTAuth::fromUser($user);

            return response()->json(compact('token'));
        } catch (JWTException $e) {
            return response()->json(['error' => 'Could not create token'], 500);
        }
    }
}
