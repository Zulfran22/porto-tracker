<template>
    <div class="logo-row">
        <div class="logo-icon" aria-hidden="true">
            <img src="/icons/wealtid-mark.svg" alt="" class="logo-mark"/>
        </div>
        <p class="wordmark">
            <span class="wordmark__w">W</span><span class="wordmark__rest">ealthID</span>
        </p>
    </div>
</template>

<style scoped>
.logo-row {
    --dur: 4.6s;
    display: flex;
    align-items: center;
    gap: clamp(14px, 5vw, 22px);
    height: clamp(84px, 24vw, 132px);
    width: fit-content;
    max-width: 100%;
}

.logo-icon {
    position: relative;
    width: clamp(52px, 16vw, 82px);
    height: clamp(68px, 21vw, 108px);
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    animation: glow var(--dur) ease-in-out infinite;
}

.logo-mark {
    height: 100%;
    width: auto;
}

@keyframes glow {
    0%, 100% { filter: drop-shadow(0 4px 10px rgba(99, 102, 241, .25)); transform: scale(1); }
    50%      { filter: drop-shadow(0 10px 24px rgba(99, 102, 241, .5)); transform: scale(1.05); }
}

.wordmark {
    font-family: 'Geist', system-ui, sans-serif;
    font-weight: 700;
    font-size: clamp(28px, 9vw, 44px);
    letter-spacing: -.03em;
    margin: 0;
    display: inline-block;
    overflow: hidden;
    white-space: nowrap;
    animation: wmWipe var(--dur) ease-out infinite;
}
.wordmark__w    { color: #6366F1; }
.wordmark__rest { color: #18181b; }
:global(.dark) .wordmark__rest { color: #fafafa; }

@keyframes wmWipe {
    0%   { clip-path: inset(0 100% 0 0); }
    35%  { clip-path: inset(0 0% 0 0); }
    85%  { clip-path: inset(0 0% 0 0); }
    100% { clip-path: inset(0 100% 0 0); }
}
</style>
