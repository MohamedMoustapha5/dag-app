import { Eye, EyeOff } from 'lucide-react';
import type { ComponentProps, Ref } from 'react';
import { useState } from 'react';

type Props = Omit<ComponentProps<'input'>, 'type'> & {
    ref?: Ref<HTMLInputElement>;
};

export default function AuthPasswordField({ ref, ...props }: Props) {
    const [showPassword, setShowPassword] = useState(false);

    return (
        <div className="input-icon-wrap-right">
            <input
                type={showPassword ? 'text' : 'password'}
                ref={ref}
                style={{ paddingRight: 40 }}
                {...props}
            />
            <button
                type="button"
                className="input-icon-clear"
                onClick={() => setShowPassword((show) => !show)}
                aria-label={
                    showPassword
                        ? 'Masquer le mot de passe'
                        : 'Afficher le mot de passe'
                }
                tabIndex={-1}
            >
                {showPassword ? <EyeOff size={15} /> : <Eye size={15} />}
            </button>
        </div>
    );
}
