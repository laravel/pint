interface   User { name: string, age: number }
export function describe( user: User ): string {
    return `${user.name} (${user.age})`
}
