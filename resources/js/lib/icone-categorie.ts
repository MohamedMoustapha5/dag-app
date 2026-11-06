import {
    Baby,
    BarChart3,
    Bot,
    Briefcase,
    Building2,
    CalendarDays,
    Calculator,
    Camera,
    Car,
    Code2,
    Dumbbell,
    Factory,
    Gamepad2,
    Globe,
    GraduationCap,
    HardHat,
    Headphones,
    HeartHandshake,
    Hospital,
    Hotel,
    Landmark,
    Languages,
    Mail,
    Megaphone,
    Mic,
    Music,
    Palette,
    PawPrint,
    PenTool,
    Plane,
    Printer,
    Scale,
    School,
    Server,
    ShieldCheck,
    Shirt,
    ShoppingCart,
    Smartphone,
    Sprout,
    TrendingUp,
    Truck,
    Users,
    Utensils,
    Video,
    Zap,
} from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import { normaliser } from '@/lib/recherche';

/**
 * Icon of a service or formation category, picked from the words of its
 * name ("Services aux hôpitaux" gets a hospital, "Cybersécurité" a shield…),
 * so a category created later by DAG gets a meaningful icon without code.
 * Rules are tried in order on the name without accents or capitals; the
 * first match wins. `\b` marks a word start, for short words that would
 * otherwise match inside longer ones.
 */
const REGLES: [RegExp, LucideIcon][] = [
    [/hopita|clinique|sante|medic|pharma|\bsoin|infirmi/, Hospital],
    [/cyber|securi|protection des donnees|\bhack/, ShieldCheck],
    [/community|communaute|reseaux sociaux|social media/, Users],
    [/contenu|redaction|copywriting|\bblog/, PenTool],
    [
        /design|graphis|graphique|\blogo|identite visuelle|branding|infographi/,
        Palette,
    ],
    [/photo/, Camera],
    [/video|montage|\bfilm|audiovisuel/, Video],
    [/podcast|\baudio|\bvoix/, Mic],
    [/strateg|publicit|\bseo\b|referencement|croissance|\bads\b/, TrendingUp],
    [/marketing|communication|\bpub\b|relations? presse/, Megaphone],
    [/mobile|application|\bapps?\b/, Smartphone],
    [
        /web|\bsite|developpe|\bcode|programm|logiciel|informatique|technique|digital|numerique/,
        Code2,
    ],
    [/e-?commerce|boutique|vente|commerce|\bshop/, ShoppingCart],
    [/\bdata\b|donnees|analyse|analytics|statisti|reporting/, BarChart3],
    [/cloud|heberg|serveur|reseau|infrastructure|maintenance/, Server],
    [
        /intelligence artificielle|\bia\b|\bai\b|automatisation|chatbot|\brobot/,
        Bot,
    ],
    [/banque|bancaire|microfinance|assurance/, Landmark],
    [/financ|comptab|fiscal|\bpaie\b|gestion financiere|audit/, Calculator],
    [/juridi|\bdroit|avocat|\blegal|notari/, Scale],
    [/immobili|batiment|construction|\bbtp\b|chantier/, HardHat],
    [/hotel|tourism|voyage|hebergement touristique/, Hotel],
    [/aerien|aviation|aeroport/, Plane],
    [/restaura|cuisine|alimentaire|traiteur/, Utensils],
    [/agricult|agro|elevage|ferme/, Sprout],
    [/transport|logisti|livraison/, Truck],
    [/automobile|garage|\bauto\b|vehicule/, Car],
    [/industri|usine|manufactur/, Factory],
    [/energie|solaire|electri/, Zap],
    [/ecole|scolaire|education|enseignement|universit|lycee|college/, School],
    [/formation|\bcours|apprenti|coaching|atelier/, GraduationCap],
    [/langue|anglais|francais|traduction/, Languages],
    [/enfan|creche|maternelle|bebe/, Baby],
    [/veterinai|animal|animaux/, PawPrint],
    [/\bong\b|association|humanitaire|caritati/, HeartHandshake],
    [/evenement|evenementiel|mariage|seminaire|conference/, CalendarDays],
    [/musique|musical|\bson\b/, Music],
    [/sport|fitness|gym/, Dumbbell],
    [/mode|couture|textile|vetement/, Shirt],
    [/jeu|gaming|esport/, Gamepad2],
    [/impression|imprimerie|\bprint/, Printer],
    [/e-?mail|newsletter|emailing/, Mail],
    [/support|assistance|service client|helpdesk/, Headphones],
    [
        /ressources humaines|\brh\b|recrutement|emploi|conseil|consulting|entreprise|gestion|\bpme\b/,
        Briefcase,
    ],
    [/administration|public|mairie|collectivit/, Building2],
    [/international|export|import/, Globe],
];

export function iconeCategorie(nom: string, parDefaut: LucideIcon): LucideIcon {
    const texte = normaliser(nom);

    for (const [motif, icone] of REGLES) {
        if (motif.test(texte)) {
            return icone;
        }
    }

    return parDefaut;
}
