// Feedback sounds played when a transaction is marked as paid. Everything is
// synthesized with the Web Audio API so no asset ships, and playback only
// happens from a user gesture (a click), which satisfies autoplay policies.
//
// The keys here must stay in sync with App\Enums\SoundTheme.

export type SoundValue = 'blip' | 'coin' | 'chime' | 'pop' | 'success';

let audioContext: AudioContext | null = null;

function getContext(): AudioContext | null {
    if (typeof window === 'undefined') {
        return null;
    }

    const AudioCtx =
        window.AudioContext ??
        (window as unknown as { webkitAudioContext?: typeof AudioContext }).webkitAudioContext;

    if (!AudioCtx) {
        return null;
    }

    audioContext ??= new AudioCtx();

    if (audioContext.state === 'suspended') {
        void audioContext.resume();
    }

    return audioContext;
}

type ToneSpec = {
    freq: number;
    start: number;
    duration: number;
    type?: OscillatorType;
    peak?: number;
    endFreq?: number;
};

function tone(ctx: AudioContext, spec: ToneSpec): void {
    const { freq, start, duration, type = 'sine', peak = 0.2, endFreq } = spec;
    const oscillator = ctx.createOscillator();
    const gain = ctx.createGain();

    oscillator.type = type;
    oscillator.frequency.setValueAtTime(freq, start);

    if (endFreq) {
        oscillator.frequency.exponentialRampToValueAtTime(endFreq, start + duration);
    }

    gain.gain.setValueAtTime(0.0001, start);
    gain.gain.exponentialRampToValueAtTime(peak, start + 0.01);
    gain.gain.exponentialRampToValueAtTime(0.0001, start + duration);

    oscillator.connect(gain).connect(ctx.destination);
    oscillator.start(start);
    oscillator.stop(start + duration + 0.02);
}

export function playSound(value: SoundValue = 'blip'): void {
    const ctx = getContext();

    if (!ctx) {
        return;
    }

    try {
        const t = ctx.currentTime;

        switch (value) {
            case 'coin':
                tone(ctx, { freq: 988, start: t, duration: 0.08, type: 'square', peak: 0.15 });
                tone(ctx, {
                    freq: 1319,
                    start: t + 0.08,
                    duration: 0.16,
                    type: 'square',
                    peak: 0.15,
                });
                break;
            case 'chime':
                tone(ctx, { freq: 1047, start: t, duration: 0.5, type: 'triangle', peak: 0.18 });
                tone(ctx, {
                    freq: 1568,
                    start: t + 0.04,
                    duration: 0.5,
                    type: 'triangle',
                    peak: 0.12,
                });
                break;
            case 'pop':
                tone(ctx, {
                    freq: 420,
                    start: t,
                    duration: 0.12,
                    type: 'square',
                    endFreq: 720,
                    peak: 0.18,
                });
                break;
            case 'success':
                tone(ctx, { freq: 523, start: t, duration: 0.12 });
                tone(ctx, { freq: 659, start: t + 0.1, duration: 0.12 });
                tone(ctx, { freq: 784, start: t + 0.2, duration: 0.2 });
                break;
            case 'blip':
            default:
                tone(ctx, { freq: 880, start: t, duration: 0.18, endFreq: 1320 });
                break;
        }
    } catch {
        // Ignore audio failures (autoplay policy, unsupported context, etc.).
    }
}
