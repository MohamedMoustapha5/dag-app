import { Check, ChevronDown } from 'lucide-react';
import { useEffect, useRef, useState, useSyncExternalStore } from 'react';
import {
    changerLangue,
    ecouterLangue,
    langueActive,
} from '@/lib/i18n/langue';

const LANGUAGES = [
    { code: 'FR', label: 'Français', flag: '🇫🇷' },
    { code: 'EN', label: 'English', flag: '🇬🇧' },
] as const;

export default function LanguageSwitcher() {
    const [isOpen, setIsOpen] = useState(false);
    const active = useSyncExternalStore(
        ecouterLangue,
        () => langueActive().toUpperCase() as (typeof LANGUAGES)[number]['code'],
        () => 'FR' as const,
    );
    const rootRef = useRef<HTMLDivElement>(null);

    useEffect(() => {
        if (!isOpen) {
            return;
        }

        const onClickOutside = (event: MouseEvent) => {
            if (!rootRef.current?.contains(event.target as Node)) {
                setIsOpen(false);
            }
        };

        const onEscape = (event: KeyboardEvent) => {
            if (event.key === 'Escape') {
                setIsOpen(false);
            }
        };

        document.addEventListener('mousedown', onClickOutside);
        document.addEventListener('keydown', onEscape);

        return () => {
            document.removeEventListener('mousedown', onClickOutside);
            document.removeEventListener('keydown', onEscape);
        };
    }, [isOpen]);

    const activeLanguage =
        LANGUAGES.find((language) => language.code === active) ?? LANGUAGES[0];

    return (
        <div
            ref={rootRef}
            translate="no"
            className={`lang-dropdown${isOpen ? ' open' : ''}`}
        >
            <button
                type="button"
                className="lang-dropdown-trigger"
                aria-haspopup="listbox"
                aria-expanded={isOpen}
                aria-label="Choisir la langue"
                onClick={() => setIsOpen((open) => !open)}
            >
                <span className="flag" aria-hidden="true">
                    {activeLanguage.flag}
                </span>
                <span className="lang-dropdown-code">
                    {activeLanguage.code}
                </span>
                <ChevronDown size={13} />
            </button>

            {isOpen && (
                <div className="lang-dropdown-panel" role="listbox">
                    {LANGUAGES.map((language) => (
                        <button
                            key={language.code}
                            type="button"
                            role="option"
                            aria-selected={language.code === active}
                            className={`lang-dropdown-option${language.code === active ? ' active' : ''}`}
                            onClick={() => {
                                changerLangue(
                                    language.code === 'EN' ? 'en' : 'fr',
                                );
                                setIsOpen(false);
                            }}
                        >
                            <span className="flag" aria-hidden="true">
                                {language.flag}
                            </span>
                            {language.label}
                            {language.code === active && (
                                <Check
                                    size={15}
                                    className="lang-dropdown-check"
                                />
                            )}
                        </button>
                    ))}
                </div>
            )}
        </div>
    );
}
