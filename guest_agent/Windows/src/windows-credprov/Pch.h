
#pragma once

// The OVirt Credentials Provider should be used on Windows Vista and above.
#define _WIN32_WINNT	0x0600
#define WINVER			0x0600

#include <windows.h>
#include <shlwapi.h>

#include <cassert>

#ifdef _DEBUG
#define ASSERT(x) assert(x)
#define VERIFY(x) ASSERT(x)
#else
#define ASSERT(x) __noop
#define VERIFY(x) (x)
#endif
