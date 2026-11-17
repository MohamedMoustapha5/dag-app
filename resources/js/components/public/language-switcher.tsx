import { Check, ChevronDown } from 'lucide-react';
import {
    useEffect,
    useId,
    useRef,
    useState,
    useSyncExternalStore,
} from 'react';
import { changerLangue, ecouterLangue, langueActive } from '@/lib/i18n/langue';

/*
 * The flags are drawn rather than written as emoji: Windows has no flag
 * emoji, so Chrome and Edge there showed the letters "FR" / "GB" instead.
 */
function DrapeauFrance() {
    return (
        <svg viewBox="0 0 3 2" preserveAspectRatio="none" aria-hidden="true">
            <rect width="1" height="2" fill="#002654" />
            <rect x="1" width="1" height="2" fill="#ffffff" />
            <rect x="2" width="1" height="2" fill="#ce1126" />
        </svg>
    );
}

function DrapeauRoyaumeUni() {
    // Unique per instance: the trigger and the menu both draw this flag.
    const id = useId();

    return (
        <svg
            viewBox="0 0 60 30"
            preserveAspectRatio="xMidYMid slice"
            aria-hidden="true"
        >
            <clipPath id={`${id}-cadre`}>
                <path d="M0,0 v30 h60 v-30 z" />
            </clipPath>
            <clipPath id={`${id}-diagonales`}>
                <path d="M30,15 h30 v15 z v15 h-30 z h-30 v-15 z v-15 h30 z" />
            </clipPath>
            <g clipPath={`url(#${id}-cadre)`}>
                <path d="M0,0 v30 h60 v-30 z" fill="#012169" />
                <path
                    d="M0,0 L60,30 M60,0 L0,30"
                    stroke="#ffffff"
                    strokeWidth="6"
                />
                <path
                    d="M0,0 L60,30 M60,0 L0,30"
                    clipPath={`url(#${id}-diagonales)`}
                    stroke="#c8102e"
                    strokeWidth="4"
                />
                <path
                    d="M30,0 v30 M0,15 h60"
                    stroke="#ffffff"
                    strokeWidth="10"
                />
                <path
                    d="M30,0 v30 M0,15 h60"
                    stroke="#c8102e"
                    strokeWidth="6"
                />
            </g>
        </svg>
    );
}

const LANGUAGES = [
    { code: 'FR', label: 'Français', Drapeau: DrapeauFrance },
    { code: 'EN', label: 'English', Drapeau: DrapeauRoyaumeUni },
] as const;

export default function LanguageSwitcher() {
    const [isOpen, setIsOpen] = useState(false);
    const active = useSyncExternalStore(
        ecouterLangue,
        () =>
            langueActive().toUpperCase() as (typeof LANGUAGES)[number]['code'],
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
                    <activeLanguage.Drapeau />
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
                                <language.Drapeau />
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
