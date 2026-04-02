<x-layouts.admin title="Create Role" header="Create Role">
    <div class="max-w-2xl">
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
            <form method="POST" action="{{ route('admin.roles.store') }}" class="p-6 space-y-6">
                @csrf

                {{-- Role Name --}}
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Role Name</label>
                    <input type="text" name="name" id="name" value="{{ old('name') }}" required
                           class="mt-1 block w-full rounded-md border-0 py-1.5 text-gray-900 dark:text-white dark:bg-gray-700 shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-600 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                    @error('name')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Permissions --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-4">Permissions</label>
                    @error('permissions')
                        <p class="mb-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror

                    <div class="space-y-6">
                        @foreach($groupedPermissions as $group => $permissions)
                            <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                                <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">{{ $group }}</h4>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    @foreach($permissions as $permission)
                                        <label class="flex items-center gap-2">
                                            <input type="checkbox" name="permissions[]" value="{{ $permission->name }}"
                                                   {{ in_array($permission->name, old('permissions', [])) ? 'checked' : '' }}
                                                   class="h-4 w-4 rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-600 dark:bg-gray-700">
                                            <span class="text-sm text-gray-700 dark:text-gray-300">{{ ucwords($permission->name) }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Actions --}}
                <div class="flex items-center justify-end gap-3 border-t border-gray-200 dark:border-gray-700 pt-6">
                    <a href="{{ route('admin.roles.index') }}"
                       class="rounded-md bg-white dark:bg-gray-700 px-3 py-2 text-sm font-semibold text-gray-900 dark:text-white shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600">
                        Cancel
                    </a>
                    <button type="submit"
                            class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                        Create Role
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-layouts.admin>
