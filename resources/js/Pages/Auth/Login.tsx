import Checkbox from '@/Components/Checkbox';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

export default function Login({
    status,
    canResetPassword,
    intended,
}: {
    status?: string;
    canResetPassword: boolean;
    intended?: string;
}) {
    const { data, setData, post, processing, errors, reset } = useForm({
        email: '',
        password: '',
        remember: false as boolean,
        intended: intended ?? '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        post(route('login'), {
            onFinish: () => reset('password'),
        });
    };

    return (
        <GuestLayout>
            <Head title="Log in" />

            {status && (
                <div className="text-primary mb-4 text-sm font-medium">
                    {status}
                </div>
            )}

            {intended && (
                <p className="text-muted-foreground mb-4 text-sm">
                    Log in to continue booking. You'll return to the event
                    after signing in.
                </p>
            )}

            <form onSubmit={submit}>
                <div>
                    <InputLabel htmlFor="email" value="Email" />

                    <TextInput
                        id="email"
                        type="email"
                        name="email"
                        value={data.email}
                        className="mt-1 block w-full"
                        autoComplete="username"
                        isFocused={true}
                        onChange={(e) => setData('email', e.target.value)}
                    />

                    <InputError message={errors.email} className="mt-2" />
                </div>

                <div className="mt-4">
                    <InputLabel htmlFor="password" value="Password" />

                    <TextInput
                        id="password"
                        type="password"
                        name="password"
                        value={data.password}
                        className="mt-1 block w-full"
                        autoComplete="current-password"
                        showPasswordToggle
                        onChange={(e) => setData('password', e.target.value)}
                    />

                    <InputError message={errors.password} className="mt-2" />
                </div>

                <div className="mt-4 block">
                    <label className="flex items-center gap-2">
                        <Checkbox
                            name="remember"
                            checked={data.remember}
                            onChange={(e) =>
                                setData(
                                    'remember',
                                    (e.target.checked || false) as false,
                                )
                            }
                        />
                        <span className="text-muted-foreground text-sm">
                            Remember me
                        </span>
                    </label>
                </div>

                <div className="mt-6 flex items-center justify-between">
                    {canResetPassword && (
                        <Link
                            href={route('password.request')}
                            className="text-muted-foreground hover:text-foreground focus:ring-ring rounded text-sm underline focus:ring-2 focus:ring-offset-2 focus:outline-none"
                        >
                            Forgot your password?
                        </Link>
                    )}

                    <div className="flex items-center gap-4">
                        <Link
                            href={route('register', intended ? { intended } : {})}
                            className="text-muted-foreground hover:text-foreground focus:ring-ring rounded text-sm underline focus:ring-2 focus:ring-offset-2 focus:outline-none"
                        >
                            Create account
                        </Link>
                        <PrimaryButton disabled={processing}>Log in</PrimaryButton>
                    </div>
                </div>
            </form>
        </GuestLayout>
    );
}
