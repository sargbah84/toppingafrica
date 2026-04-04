<div x-data="{
    questions: @js($questions),
    passingScore: {{ $passingScore }},
    showAnswers: @js($showAnswers),
    currentIndex: 0,
    selectedAnswers: {},
    submitted: false,
    score: 0,

    get currentQuestion() { return this.questions[this.currentIndex]; },
    get totalQuestions() { return this.questions.length; },
    get progress() { return Math.round(((this.currentIndex + 1) / this.totalQuestions) * 100); },
    get passed() { return this.score >= this.passingScore; },
    get answeredCount() { return Object.keys(this.selectedAnswers).length; },

    selectAnswer(answerIndex) {
        if (this.submitted) return;
        this.selectedAnswers[this.currentIndex] = answerIndex;
    },

    next() {
        if (this.currentIndex < this.totalQuestions - 1) this.currentIndex++;
    },
    prev() {
        if (this.currentIndex > 0) this.currentIndex--;
    },

    submit() {
        if (this.answeredCount < this.totalQuestions) return;
        let correct = 0;
        this.questions.forEach((q, qi) => {
            const selected = this.selectedAnswers[qi];
            if (q.answers[selected]?.is_correct) correct++;
        });
        this.score = Math.round((correct / this.totalQuestions) * 100);
        this.submitted = true;
    },

    restart() {
        this.currentIndex = 0;
        this.selectedAnswers = {};
        this.submitted = false;
        this.score = 0;
    }
}" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">

    {{-- Results Screen --}}
    <template x-if="submitted">
        <div class="p-8 text-center">
            <div class="w-20 h-20 mx-auto mb-4 rounded-full flex items-center justify-center"
                 :class="passed ? 'bg-green-100 dark:bg-green-900/30' : 'bg-red-100 dark:bg-red-900/30'">
                <span class="text-3xl font-bold" :class="passed ? 'text-green-600' : 'text-red-600'" x-text="score + '%'"></span>
            </div>
            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-1" x-text="passed ? 'Great job!' : 'Better luck next time!'"></h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">
                You need <span x-text="passingScore"></span>% to pass.
            </p>

            {{-- Answer Review --}}
            <template x-if="showAnswers">
                <div class="text-left space-y-4 mb-6 max-w-lg mx-auto">
                    <template x-for="(q, qi) in questions" :key="qi">
                        <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                            <p class="text-sm font-semibold text-gray-900 dark:text-white mb-2" x-text="(qi+1) + '. ' + q.question"></p>
                            <template x-for="(a, ai) in q.answers" :key="ai">
                                <div class="flex items-center gap-2 py-1 text-sm"
                                     :class="{
                                         'text-green-600 dark:text-green-400 font-medium': a.is_correct,
                                         'text-red-500 line-through': selectedAnswers[qi] === ai && !a.is_correct,
                                         'text-gray-600 dark:text-gray-400': !a.is_correct && selectedAnswers[qi] !== ai
                                     }">
                                    <span x-show="a.is_correct" class="flex-shrink-0">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd"/></svg>
                                    </span>
                                    <span x-show="!a.is_correct && selectedAnswers[qi] === ai" class="flex-shrink-0">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z"/></svg>
                                    </span>
                                    <span x-text="a.text"></span>
                                </div>
                            </template>
                            <template x-if="q.explanation">
                                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400 italic" x-text="q.explanation"></p>
                            </template>
                        </div>
                    </template>
                </div>
            </template>

            <div class="flex items-center justify-center gap-3">
                <button @click="restart" class="px-5 py-2.5 border border-gray-300 dark:border-gray-600 text-sm font-semibold rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                    Try Again
                </button>
            </div>
        </div>
    </template>

    {{-- Quiz Flow --}}
    <template x-if="!submitted">
        <div>
            {{-- Progress Bar --}}
            <div class="h-1 bg-gray-200 dark:bg-gray-700">
                <div class="h-1 bg-primary transition-all duration-300" :style="'width: ' + progress + '%'"></div>
            </div>

            <div class="p-6">
                {{-- Question Counter --}}
                <div class="flex items-center justify-between mb-4">
                    <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">
                        Question <span x-text="currentIndex + 1"></span> of <span x-text="totalQuestions"></span>
                    </span>
                    <span class="text-xs text-gray-400 dark:text-gray-500" x-text="answeredCount + '/' + totalQuestions + ' answered'"></span>
                </div>

                {{-- Question --}}
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-5" x-text="currentQuestion.question"></h3>

                {{-- Answers --}}
                <div class="space-y-2 mb-6">
                    <template x-for="(answer, ai) in currentQuestion.answers" :key="ai">
                        <button @click="selectAnswer(ai)"
                                class="w-full text-left px-4 py-3 rounded-lg border-2 text-sm font-medium transition-all"
                                :class="selectedAnswers[currentIndex] === ai
                                    ? 'border-primary bg-primary/5 text-primary'
                                    : 'border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:border-gray-300 dark:hover:border-gray-600'">
                            <span class="inline-flex items-center gap-3">
                                <span class="w-6 h-6 rounded-full border-2 flex items-center justify-center text-xs flex-shrink-0"
                                      :class="selectedAnswers[currentIndex] === ai ? 'border-primary bg-primary text-white' : 'border-gray-300 dark:border-gray-600'"
                                      x-text="String.fromCharCode(65 + ai)"></span>
                                <span x-text="answer.text"></span>
                            </span>
                        </button>
                    </template>
                </div>

                {{-- Navigation --}}
                <div class="flex items-center justify-between">
                    <button @click="prev" x-show="currentIndex > 0"
                            class="px-4 py-2 text-sm font-medium text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors">
                        &larr; Previous
                    </button>
                    <div x-show="currentIndex === 0"></div>

                    <template x-if="currentIndex < totalQuestions - 1">
                        <button @click="next"
                                class="px-5 py-2.5 bg-primary text-white text-sm font-semibold rounded-lg hover:bg-primary-hover transition-colors">
                            Next &rarr;
                        </button>
                    </template>
                    <template x-if="currentIndex === totalQuestions - 1">
                        <button @click="submit"
                                :disabled="answeredCount < totalQuestions"
                                class="px-5 py-2.5 bg-primary text-white text-sm font-semibold rounded-lg hover:bg-primary-hover transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                            Submit Quiz
                        </button>
                    </template>
                </div>
            </div>
        </div>
    </template>
</div>
